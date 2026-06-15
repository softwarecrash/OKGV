import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const updateThemeToggle = () => {
    const theme = document.documentElement.getAttribute('data-bs-theme') ?? 'light';
    const icon = document.querySelector('[data-theme-icon]');
    const label = document.querySelector('[data-theme-label]');

    if (icon) {
        icon.textContent = theme === 'dark' ? '☀' : '☾';
    }

    if (label) {
        label.textContent = theme === 'dark' ? 'Helle Darstellung' : 'Dunkle Darstellung';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    updateThemeToggle();

    document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') ?? 'light';
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-bs-theme', nextTheme);

        try {
            localStorage.setItem('okgv-theme', nextTheme);
        } catch {
            // The selected theme still applies for the current page.
        }

        updateThemeToggle();
    });

    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const inputId = toggle.getAttribute('aria-controls');
        const input = inputId ? document.getElementById(inputId) : null;

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        toggle.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            const label = showPassword ? 'Passwort verbergen' : 'Passwort anzeigen';

            input.type = showPassword ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(showPassword));
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
            toggle.querySelector('[data-password-show-icon]')?.classList.toggle('d-none', showPassword);
            toggle.querySelector('[data-password-hide-icon]')?.classList.toggle('d-none', !showPassword);
            input.focus();
        });
    });

    document.querySelectorAll('[data-parcel-map-editor]').forEach((editor) => {
        const svg = editor.querySelector('[data-map-svg]');
        const polygonElement = editor.querySelector('[data-map-polygon]');
        const handlesElement = editor.querySelector('[data-map-handles]');
        const selection = editor.querySelector('[data-map-parcel-select]');
        const form = editor.querySelector('[data-map-form]');
        const polygonInput = editor.querySelector('[data-map-polygon-input]');
        const removeInput = editor.querySelector('[data-map-remove-input]');
        const drawButton = editor.querySelector('[data-map-draw]');
        const undoButton = editor.querySelector('[data-map-undo]');
        const clearButton = editor.querySelector('[data-map-clear]');
        const saveButton = editor.querySelector('[data-map-save]');
        const pointCount = editor.querySelector('[data-map-point-count]');
        const help = editor.querySelector('[data-map-help]');

        if (!(svg instanceof SVGSVGElement)
            || !(polygonElement instanceof SVGPolygonElement)
            || !(handlesElement instanceof SVGGElement)
            || !(selection instanceof HTMLSelectElement)
            || !(form instanceof HTMLFormElement)
            || !(polygonInput instanceof HTMLInputElement)
            || !(removeInput instanceof HTMLInputElement)) {
            return;
        }

        let points = [];
        let drawing = false;
        let drag = null;

        const dimensions = {
            width: Number(editor.dataset.width),
            height: Number(editor.dataset.height),
        };

        const svgPoint = (event) => {
            const matrix = svg.getScreenCTM();

            if (!matrix) {
                return null;
            }

            const point = new DOMPoint(event.clientX, event.clientY)
                .matrixTransform(matrix.inverse());

            return {
                x: Math.max(0, Math.min(dimensions.width, Number(point.x.toFixed(2)))),
                y: Math.max(0, Math.min(dimensions.height, Number(point.y.toFixed(2)))),
            };
        };

        const update = () => {
            polygonElement.setAttribute(
                'points',
                points.map((point) => `${point.x},${point.y}`).join(' '),
            );
            polygonInput.value = JSON.stringify(points);
            pointCount.textContent = `${points.length} ${points.length === 1 ? 'Punkt' : 'Punkte'}`;
            undoButton.disabled = points.length === 0;
            clearButton.disabled = points.length === 0;
            saveButton.disabled = !selection.value || (points.length > 0 && points.length < 3);
            handlesElement.replaceChildren();

            points.forEach((point, index) => {
                const handle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                handle.setAttribute('cx', point.x);
                handle.setAttribute('cy', point.y);
                handle.setAttribute('r', '9');
                handle.setAttribute('class', 'parcel-map-editor-handle');
                handle.dataset.index = String(index);
                handlesElement.append(handle);
            });
        };

        const selectParcel = () => {
            const option = selection.selectedOptions[0];
            points = option?.dataset.polygon
                ? JSON.parse(option.dataset.polygon)
                : [];
            form.action = option?.dataset.action ?? '';
            polygonElement.style.fill = option?.dataset.color ?? '#66BB6A';
            drawing = false;
            removeInput.value = '0';
            drawButton.disabled = !selection.value;
            help.textContent = selection.value
                ? 'Ziehe vorhandene Eckpunkte oder die Fläche. Mit „Punkte zeichnen“ setzt du eine neue Form.'
                : 'Wähle eine Parzelle.';
            update();
        };

        selection.addEventListener('change', selectParcel);

        drawButton?.addEventListener('click', () => {
            drawing = true;
            points = [];
            removeInput.value = '0';
            help.textContent = 'Zeichenmodus: Klicke die Eckpunkte der Parzelle der Reihe nach an. Mindestens drei Punkte sind erforderlich.';
            update();
        });

        undoButton?.addEventListener('click', () => {
            points.pop();
            removeInput.value = '0';
            update();
        });

        clearButton?.addEventListener('click', () => {
            points = [];
            drawing = false;
            removeInput.value = '1';
            help.textContent = 'Die Fläche wird beim Speichern aus dem Lageplan entfernt. Der Parzellendatensatz bleibt erhalten.';
            update();
        });

        svg.addEventListener('pointerdown', (event) => {
            if (!selection.value) {
                return;
            }

            const point = svgPoint(event);

            if (!point) {
                return;
            }

            if (event.target instanceof SVGCircleElement) {
                drag = {
                    type: 'point',
                    index: Number(event.target.dataset.index),
                };
                svg.setPointerCapture(event.pointerId);
                return;
            }

            if (event.target === polygonElement && points.length >= 3 && !drawing) {
                drag = {
                    type: 'polygon',
                    start: point,
                    original: points.map((item) => ({ ...item })),
                };
                svg.setPointerCapture(event.pointerId);
                return;
            }

            if (drawing) {
                points.push(point);
                removeInput.value = '0';
                update();
            }
        });

        svg.addEventListener('pointermove', (event) => {
            if (!drag) {
                return;
            }

            const point = svgPoint(event);

            if (!point) {
                return;
            }

            if (drag.type === 'point') {
                points[drag.index] = point;
            } else {
                const deltaX = point.x - drag.start.x;
                const deltaY = point.y - drag.start.y;
                const minX = Math.min(...drag.original.map((item) => item.x));
                const maxX = Math.max(...drag.original.map((item) => item.x));
                const minY = Math.min(...drag.original.map((item) => item.y));
                const maxY = Math.max(...drag.original.map((item) => item.y));
                const boundedX = Math.max(-minX, Math.min(dimensions.width - maxX, deltaX));
                const boundedY = Math.max(-minY, Math.min(dimensions.height - maxY, deltaY));

                points = drag.original.map((item) => ({
                    x: Number((item.x + boundedX).toFixed(2)),
                    y: Number((item.y + boundedY).toFixed(2)),
                }));
            }

            removeInput.value = '0';
            update();
        });

        const stopDragging = (event) => {
            if (drag && svg.hasPointerCapture(event.pointerId)) {
                svg.releasePointerCapture(event.pointerId);
            }

            drag = null;
        };

        svg.addEventListener('pointerup', stopDragging);
        svg.addEventListener('pointercancel', stopDragging);
        update();
    });
});
