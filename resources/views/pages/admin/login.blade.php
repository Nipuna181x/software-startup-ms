<x-layouts::auth :title="__('Platform admin login')">
    <div class="flex flex-col gap-6">
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-zinc-100 px-3 py-1.5 text-[10px] font-medium"><flux:icon icon="shield-check" class="size-3"/> PLATFORM ADMINISTRATION</span>
        <x-auth-header :title="__('Welcome back.')" :description="__('Sign in to oversee organizations on Startsuite.')" />
        <form method="POST" action="{{ route('admin.login.store') }}" class="grid gap-5">
            @csrf
            <flux:input name="email" label="Admin email" type="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@startsuite.test" />
            <flux:input name="password" label="Password" type="password" required autocomplete="current-password" viewable />
            <button type="submit" class="button-lime mt-1 w-full">Sign in to admin <span aria-hidden="true">↗</span></button>
        </form>
        <p class="border-t border-zinc-100 pt-5 text-xs leading-6 text-zinc-500">Signing in to your team’s workspace? <a href="{{ route('login') }}" class="font-medium text-zinc-950 underline underline-offset-4">Use the workspace login</a>.</p>
    </div>
</x-layouts::auth>
