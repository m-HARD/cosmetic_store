<template>
    <div class="grid h-screen grid-cols-12 gap-3 p-3">
        <section class="col-span-12"><slot name="top" /></section>
        <aside class="col-span-2"><slot name="left" /></aside>
        <main class="col-span-6"><slot name="center" /></main>
        <section class="col-span-4"><slot name="right" /></section>
    </div>
</template>
