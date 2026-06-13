<script setup>
defineProps({
    loginForm: { type: Object, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits(['submit']);
</script>

<template>
    <section class="auth-page">
        <form class="auth-legacy-form" @submit.prevent="$emit('submit')">
            <h1 class="auth-legacy-title">PLEASE LOGIN HERE</h1>
            <div class="auth-legacy-panel">
                <label class="auth-row">
                    <span>Email</span>
                    <input v-model="loginForm.email" type="email" placeholder="Your Email" required>
                </label>
                <label class="auth-row">
                    <span>Password</span>
                    <input v-model="loginForm.password" type="password" placeholder="Your Password" required>
                </label>
                <label class="auth-row">
                    <span>Category</span>
                    <select v-model="loginForm.role" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
                <div class="auth-actions">
                    <button type="submit" :disabled="busy">{{ busy ? 'WORKING...' : 'SUBMIT' }}</button>
                </div>
            </div>
        </form>
    </section>
</template>
