{{--
    Compiled Tailwind (Vite) for pages that used to load cdn.tailwindcss.com (Tailwind v3).
    The compat rules below restore the v3 defaults v4 changed, so those pages look identical.
    Rules live in @layer base, so every utility class (border-green-200, etc.) still wins.
--}}
@vite('resources/css/app.css')
<style>
@layer base {
    *, ::after, ::before, ::backdrop, ::file-selector-button { border-color: #e5e7eb; }
    button:not(:disabled), [role="button"]:not(:disabled) { cursor: pointer; }
    ::placeholder { color: #9ca3af; }
}
</style>
