@props(['count' => 0, 'label' => 'Offene Aufgabe'])

@if ($count > 0)
    <span class="action-indicator ms-1"
          title="{{ $count }} {{ $label }}"
          aria-label="{{ $count }} {{ $label }}">
        <span class="visually-hidden">{{ $count }} {{ $label }}</span>
    </span>
@endif
