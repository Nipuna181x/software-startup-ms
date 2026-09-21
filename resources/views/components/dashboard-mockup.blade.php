@props(['compact' => false])
<div {{ $attributes->class(['product-scene relative isolate']) }} aria-label="Illustrative preview of the planned project workspace">
    <div class="absolute inset-5 rounded-full bg-[#eff7de]/70 blur-3xl"></div>
    <div class="relative ml-4 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_24px_70px_-30px_#25252540] sm:ml-10">
        <div class="flex items-center gap-1.5 border-b border-zinc-100 px-5 py-4"><span class="size-2 rounded-full bg-[#ff9f8e]"></span><span class="size-2 rounded-full bg-[#ffe08a]"></span><span class="size-2 rounded-full bg-lime"></span><span class="ml-auto text-[10px] text-zinc-400">Workspace preview</span></div>
        <div class="flex">
            <div class="hidden w-14 shrink-0 flex-col items-center gap-6 border-r border-zinc-100 pt-6 sm:flex"><flux:icon icon="squares-2x2" class="size-5"/><flux:icon icon="folder" class="size-4 text-zinc-400"/><flux:icon icon="check-circle" class="size-4 text-zinc-400"/><flux:icon icon="users" class="size-4 text-zinc-400"/></div>
            <div class="min-w-0 flex-1 p-5 sm:p-7">
                <div class="flex items-center justify-between"><span class="text-[10px] tracking-wider text-zinc-400 uppercase">Product / Website</span><span class="rounded-full bg-[#eef8e4] px-2 py-1 text-[9px] text-green-800">On track</span></div>
                <h3 class="mt-3 text-xl font-medium tracking-tight sm:text-2xl">Good ideas, taking shape.</h3><p class="mt-2 text-[11px] text-zinc-500">A shared plan. A clear next step.</p>
                <div class="mt-6 flex items-center justify-between border-b border-zinc-100 pb-3 text-[10px]"><span class="font-medium">Board view <span class="ml-4 font-normal text-zinc-400">Overview</span></span><span class="rounded bg-lime px-2 py-1">+ New task</span></div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-[10px] sm:grid-cols-3">
                    @foreach ([['To do', 'Define the product vision', 'Planning', '#f0ebff'], ['In progress', 'Design a better experience', 'Design', '#fff3d4'], ['Done', 'Bring the team together', 'Team', '#e9f4ff']] as $column)
                        <div @class(['rounded-lg bg-zinc-50 p-2.5', 'hidden sm:block' => $loop->last])><p class="mb-3 text-zinc-500">{{ $column[0] }} <span class="float-right">1</span></p><div class="rounded-lg border border-zinc-100 bg-white p-3 shadow-xs"><span class="rounded px-1.5 py-1 text-[8px]" style="background: {{ $column[3] }}">{{ $column[2] }}</span><p class="mt-3 min-h-10 font-medium leading-4">{{ $column[1] }}</p><div class="mt-4 flex items-center justify-between"><span class="grid size-5 place-items-center rounded-full bg-[#eee9e2] text-[8px]">{{ ['AM', 'JL', 'SK'][$loop->index] }}</span><flux:icon icon="chat-bubble-oval-left" class="size-3 text-zinc-400"/></div></div></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="relative -mt-5 mr-12 flex w-fit items-center gap-4 rounded-xl border border-zinc-100 bg-white px-5 py-4 shadow-[0_12px_40px_-15px_#0003] sm:-mt-7"><span class="grid size-10 place-items-center rounded-full bg-lime"><flux:icon icon="check" class="size-5"/></span><div><p class="text-sm font-medium">Small steps. Big progress.</p><p class="mt-1 text-[11px] text-zinc-500">From the first idea to the final release.</p></div></div>
</div>
