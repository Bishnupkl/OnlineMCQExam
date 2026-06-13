<script setup>
defineProps({
    loginForm: { type: Object, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits(['submit']);
</script>

<template>
    <section class="legacy-container form-page">
        <form class="legacy-form" @submit.prevent="$emit('submit')">
            <h2>LogIn</h2>
            <label>Role
                <select v-model="loginForm.role">
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <label>Email <input v-model="loginForm.email" type="email" required></label>
            <label>Password <input v-model="loginForm.password" type="password" required></label>
            <button class="legacy-primary" :disabled="busy">{{ busy ? 'Working...' : 'LogIn' }}</button>
        </form>
    </section>
</template>
