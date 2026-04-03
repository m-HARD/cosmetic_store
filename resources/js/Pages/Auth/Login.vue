<script setup>
import { useForm } from '@inertiajs/vue3';

// نموذج Inertia يتعامل تلقائياً مع CSRF والأخطاء.
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4" dir="rtl">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
            <h1 class="mb-2 text-center text-2xl font-bold text-slate-900">تسجيل الدخول</h1>
            <p class="mb-6 text-center text-sm text-slate-600">نظام نقاط البيع — متجر مستحضرات التجميل</p>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">البريد الإلكتروني</label>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">كلمة المرور</label>
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        required
                    />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.remember" type="checkbox" class="rounded border-slate-300" />
                    تذكرني
                </label>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'جاري الدخول…' : 'دخول' }}
                </button>
            </form>
        </div>
    </div>
</template>
