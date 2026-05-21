@props(['name'])

@error($name)
    <p {{ $attributes->class(['mt-1.5 text-sm text-red-600']) }} role="alert">{{ $message }}</p>
@enderror
