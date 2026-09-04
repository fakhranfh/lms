{{--
    Atom: trailing filler cells for a 3-column <x-ui.person-grid>, so the
    last row's empty cells read as plain background instead of the grid
    gap color. Pass the number of real cards already rendered.
--}}
@props(['count' => 0])

@for ($i = 0; $i < (3 - $count % 3) % 3; $i++)
    <div class="bg-surface hidden md:block"></div>
@endfor
