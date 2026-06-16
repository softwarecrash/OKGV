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

    document.querySelectorAll('[data-demo-login]').forEach((button) => {
        button.addEventListener('click', () => {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            if (emailInput instanceof HTMLInputElement) {
                emailInput.value = button.dataset.demoEmail ?? '';
            }

            if (passwordInput instanceof HTMLInputElement) {
                passwordInput.value = button.dataset.demoPassword ?? '';
            }

            passwordInput?.focus();
        });
    });

    const letterMemberSelect = document.getElementById('member_id');
    const letterRecipientFields = document.querySelector('[data-letter-recipient-fields]');

    if (letterMemberSelect instanceof HTMLSelectElement
        && letterRecipientFields instanceof HTMLFieldSetElement) {
        const recipientInputs = {
            recipientName: document.getElementById('recipient_name'),
            recipientStreet: document.getElementById('street'),
            recipientZip: document.getElementById('zip'),
            recipientCity: document.getElementById('city'),
        };

        letterMemberSelect.addEventListener('change', () => {
            const selectedOption = letterMemberSelect.selectedOptions.item(0);

            Object.entries(recipientInputs).forEach(([dataKey, input]) => {
                if (input instanceof HTMLInputElement) {
                    input.value = selectedOption?.dataset[dataKey] ?? '';
                }
            });
        });
    }

    const registrationMemberSelect = document.querySelector('[data-registration-member-select]');

    if (registrationMemberSelect instanceof HTMLSelectElement) {
        const preview = document.querySelector('[data-registration-member-preview]');
        const previewName = document.querySelector('[data-registration-member-name]');
        const previewEmail = document.querySelector('[data-registration-member-email]');
        const emailChoice = document.querySelector('[data-registration-email-choice]');
        const existingEmail = document.querySelector('[data-registration-existing-email]');
        const defaultEmailAction = document.querySelector('[data-registration-email-default]');
        const registrationEmail = registrationMemberSelect.dataset.registrationEmail ?? '';

        const updateRegistrationMemberPreview = () => {
            const option = registrationMemberSelect.selectedOptions.item(0);
            const memberName = option?.dataset.memberName ?? '';
            const memberEmail = option?.dataset.memberEmail ?? '';
            const hasSelection = registrationMemberSelect.value !== '';
            const emailsDiffer = hasSelection
                && memberEmail.toLocaleLowerCase() !== registrationEmail.toLocaleLowerCase();

            preview?.classList.toggle('d-none', !hasSelection);
            emailChoice?.classList.toggle('d-none', !emailsDiffer);

            if (previewName instanceof HTMLElement) {
                previewName.textContent = memberName;
            }

            if (previewEmail instanceof HTMLElement) {
                previewEmail.textContent = memberEmail || 'Keine E-Mail hinterlegt';
            }

            if (existingEmail instanceof HTMLElement) {
                existingEmail.textContent = memberEmail || 'Keine E-Mail hinterlegt';
            }

            if (defaultEmailAction instanceof HTMLInputElement) {
                defaultEmailAction.disabled = emailsDiffer;
            }
        };

        registrationMemberSelect.addEventListener('change', updateRegistrationMemberPreview);
        updateRegistrationMemberPreview();
    }

    document.querySelectorAll('[data-parcel-map-zoom]').forEach((map) => {
        const viewport = map.querySelector('[data-map-viewport]');
        const target = map.querySelector('[data-map-zoom-target]');
        const zoomIn = map.querySelector('[data-map-zoom-in]');
        const zoomOut = map.querySelector('[data-map-zoom-out]');
        const zoomReset = map.querySelector('[data-map-zoom-reset]');
        const zoomLabel = map.querySelector('[data-map-zoom-label]');

        if (!(viewport instanceof HTMLElement)
            || !(target instanceof SVGSVGElement)
            || !(zoomIn instanceof HTMLButtonElement)
            || !(zoomOut instanceof HTMLButtonElement)
            || !(zoomReset instanceof HTMLButtonElement)
            || !(zoomLabel instanceof HTMLElement)) {
            return;
        }

        const minimumZoom = 1;
        const maximumZoom = 4;
        const zoomStep = 0.25;
        const isEditor = map.matches('[data-parcel-map-editor]');
        let zoom = minimumZoom;
        let panDrag = null;
        let suppressClick = false;

        const updateEditorHandles = () => {
            const handleRadius = Number(map.dataset.mapHandleRadius ?? 9);

            map.querySelectorAll('[data-map-handles] circle').forEach((handle) => {
                handle.setAttribute('r', String(handleRadius / zoom));
            });
        };

        const applyZoom = (nextZoom, focalPoint = null) => {
            const previousWidth = target.getBoundingClientRect().width;
            const previousHeight = target.getBoundingClientRect().height;
            zoom = Math.max(minimumZoom, Math.min(maximumZoom, nextZoom));
            map.dataset.mapZoom = String(zoom);

            const relativeX = focalPoint
                ? (viewport.scrollLeft + focalPoint.x) / Math.max(previousWidth, 1)
                : (viewport.scrollLeft + (viewport.clientWidth / 2)) / Math.max(previousWidth, 1);
            const relativeY = focalPoint
                ? (viewport.scrollTop + focalPoint.y) / Math.max(previousHeight, 1)
                : (viewport.scrollTop + (viewport.clientHeight / 2)) / Math.max(previousHeight, 1);

            target.style.width = `${zoom * 100}%`;
            zoomLabel.textContent = `${Math.round(zoom * 100)} %`;
            zoomIn.disabled = zoom >= maximumZoom;
            zoomOut.disabled = zoom <= minimumZoom;
            viewport.classList.toggle('is-pannable', zoom > minimumZoom);
            updateEditorHandles();

            requestAnimationFrame(() => {
                if (zoom === minimumZoom) {
                    viewport.scrollTo({ left: 0, top: 0 });

                    return;
                }

                const currentWidth = target.getBoundingClientRect().width;
                const currentHeight = target.getBoundingClientRect().height;
                const offsetX = focalPoint?.x ?? viewport.clientWidth / 2;
                const offsetY = focalPoint?.y ?? viewport.clientHeight / 2;

                viewport.scrollLeft = Math.max(0, (relativeX * currentWidth) - offsetX);
                viewport.scrollTop = Math.max(0, (relativeY * currentHeight) - offsetY);
            });
        };

        zoomIn.addEventListener('click', () => applyZoom(zoom + zoomStep));
        zoomOut.addEventListener('click', () => applyZoom(zoom - zoomStep));
        zoomReset.addEventListener('click', () => applyZoom(minimumZoom));

        viewport.addEventListener('wheel', (event) => {
            if (!event.ctrlKey && !event.metaKey) {
                return;
            }

            event.preventDefault();
            const bounds = viewport.getBoundingClientRect();
            const focalPoint = {
                x: event.clientX - bounds.left,
                y: event.clientY - bounds.top,
            };

            applyZoom(zoom + (event.deltaY < 0 ? zoomStep : -zoomStep), focalPoint);
        }, { passive: false });

        viewport.addEventListener('pointerdown', (event) => {
            if (zoom <= minimumZoom || event.button !== 0) {
                return;
            }

            const targetIsEditorControl = isEditor
                && (map.dataset.mapDrawing === 'true'
                    || event.target instanceof SVGCircleElement
                    || event.target === map.querySelector('[data-map-polygon]'));

            if (targetIsEditorControl) {
                return;
            }

            panDrag = {
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
                scrollLeft: viewport.scrollLeft,
                scrollTop: viewport.scrollTop,
                moved: false,
            };
            viewport.classList.add('is-panning');
        });

        viewport.addEventListener('pointermove', (event) => {
            if (!panDrag || panDrag.pointerId !== event.pointerId) {
                return;
            }

            const deltaX = event.clientX - panDrag.startX;
            const deltaY = event.clientY - panDrag.startY;

            if (Math.abs(deltaX) > 3 || Math.abs(deltaY) > 3) {
                panDrag.moved = true;

                if (!viewport.hasPointerCapture(event.pointerId)) {
                    viewport.setPointerCapture(event.pointerId);
                }
            }

            if (panDrag.moved) {
                event.preventDefault();
            }

            viewport.scrollLeft = panDrag.scrollLeft - deltaX;
            viewport.scrollTop = panDrag.scrollTop - deltaY;
        });

        const stopPanning = (event) => {
            if (!panDrag || panDrag.pointerId !== event.pointerId) {
                return;
            }

            suppressClick = panDrag.moved;
            window.setTimeout(() => {
                suppressClick = false;
            }, 0);

            if (viewport.hasPointerCapture(event.pointerId)) {
                viewport.releasePointerCapture(event.pointerId);
            }

            panDrag = null;
            viewport.classList.remove('is-panning');
        };

        viewport.addEventListener('pointerup', stopPanning);
        viewport.addEventListener('pointercancel', stopPanning);
        viewport.addEventListener('click', (event) => {
            if (!suppressClick) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            suppressClick = false;
        }, true);

        applyZoom(minimumZoom);
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
        editor.dataset.mapDrawing = 'false';

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
            const zoom = Number(editor.dataset.mapZoom ?? 1);
            const handleRadius = Number(editor.dataset.mapHandleRadius ?? 9);

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
                handle.setAttribute('r', String(handleRadius / zoom));
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
            editor.dataset.mapDrawing = 'false';
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
            editor.dataset.mapDrawing = 'true';
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
            editor.dataset.mapDrawing = 'false';
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

    document.querySelectorAll('[data-private-photo-modal]').forEach((modal) => {
        const image = modal.querySelector('[data-private-photo-image]');
        const name = modal.querySelector('[data-private-photo-name]');
        const viewport = modal.querySelector('[data-private-photo-viewport]');
        const zoomIn = modal.querySelector('[data-private-photo-zoom-in]');
        const zoomOut = modal.querySelector('[data-private-photo-zoom-out]');
        const reset = modal.querySelector('[data-private-photo-reset]');
        const zoomLabel = modal.querySelector('[data-private-photo-zoom-label]');

        if (!(image instanceof HTMLImageElement)
            || !(name instanceof HTMLElement)
            || !(viewport instanceof HTMLElement)
            || !(zoomIn instanceof HTMLButtonElement)
            || !(zoomOut instanceof HTMLButtonElement)
            || !(reset instanceof HTMLButtonElement)
            || !(zoomLabel instanceof HTMLElement)) {
            return;
        }

        const minimumZoom = 1;
        const maximumZoom = 5;
        const zoomStep = 0.25;
        let zoom = minimumZoom;
        let panDrag = null;

        const applyZoom = (nextZoom, focalPoint = null) => {
            const previousWidth = image.getBoundingClientRect().width;
            const previousHeight = image.getBoundingClientRect().height;
            zoom = Math.max(minimumZoom, Math.min(maximumZoom, nextZoom));

            const relativeX = focalPoint
                ? (viewport.scrollLeft + focalPoint.x) / Math.max(previousWidth, 1)
                : (viewport.scrollLeft + (viewport.clientWidth / 2)) / Math.max(previousWidth, 1);
            const relativeY = focalPoint
                ? (viewport.scrollTop + focalPoint.y) / Math.max(previousHeight, 1)
                : (viewport.scrollTop + (viewport.clientHeight / 2)) / Math.max(previousHeight, 1);

            image.style.width = `${zoom * 100}%`;
            zoomLabel.textContent = `${Math.round(zoom * 100)} %`;
            zoomIn.disabled = zoom >= maximumZoom;
            zoomOut.disabled = zoom <= minimumZoom;
            viewport.classList.toggle('is-zoomed', zoom > minimumZoom);

            requestAnimationFrame(() => {
                if (zoom === minimumZoom) {
                    viewport.scrollTo({ left: 0, top: 0 });

                    return;
                }

                const currentWidth = image.getBoundingClientRect().width;
                const currentHeight = image.getBoundingClientRect().height;
                const offsetX = focalPoint?.x ?? viewport.clientWidth / 2;
                const offsetY = focalPoint?.y ?? viewport.clientHeight / 2;

                viewport.scrollLeft = Math.max(0, (relativeX * currentWidth) - offsetX);
                viewport.scrollTop = Math.max(0, (relativeY * currentHeight) - offsetY);
            });
        };

        modal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;

            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            image.src = trigger.dataset.privatePhotoUrl ?? '';
            name.textContent = trigger.dataset.privatePhotoName ?? '';
            applyZoom(minimumZoom);
        });

        modal.addEventListener('hidden.bs.modal', () => {
            image.removeAttribute('src');
            name.textContent = '';
            panDrag = null;
            viewport.classList.remove('is-panning');
            applyZoom(minimumZoom);
        });

        zoomIn.addEventListener('click', () => applyZoom(zoom + zoomStep));
        zoomOut.addEventListener('click', () => applyZoom(zoom - zoomStep));
        reset.addEventListener('click', () => applyZoom(minimumZoom));

        viewport.addEventListener('wheel', (event) => {
            if (!event.ctrlKey && !event.metaKey) {
                return;
            }

            event.preventDefault();
            const bounds = viewport.getBoundingClientRect();

            applyZoom(
                zoom + (event.deltaY < 0 ? zoomStep : -zoomStep),
                {
                    x: event.clientX - bounds.left,
                    y: event.clientY - bounds.top,
                },
            );
        }, { passive: false });

        viewport.addEventListener('pointerdown', (event) => {
            if (zoom <= minimumZoom || event.button !== 0) {
                return;
            }

            event.preventDefault();
            panDrag = {
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
                scrollLeft: viewport.scrollLeft,
                scrollTop: viewport.scrollTop,
            };
            viewport.classList.add('is-panning');
            viewport.setPointerCapture(event.pointerId);
        });

        viewport.addEventListener('pointermove', (event) => {
            if (!panDrag || panDrag.pointerId !== event.pointerId) {
                return;
            }

            viewport.scrollLeft = panDrag.scrollLeft - (event.clientX - panDrag.startX);
            viewport.scrollTop = panDrag.scrollTop - (event.clientY - panDrag.startY);
        });

        const stopPanning = (event) => {
            if (!panDrag || panDrag.pointerId !== event.pointerId) {
                return;
            }

            if (viewport.hasPointerCapture(event.pointerId)) {
                viewport.releasePointerCapture(event.pointerId);
            }

            panDrag = null;
            viewport.classList.remove('is-panning');
        };

        viewport.addEventListener('pointerup', stopPanning);
        viewport.addEventListener('pointercancel', stopPanning);
        applyZoom(minimumZoom);
    });
});
