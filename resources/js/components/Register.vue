<script setup>
defineProps({
    registerForm: { type: Object, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits(['submit']);
</script>

<template>
    <section class="legacy-container form-page">
        <form class="legacy-form wide" @submit.prevent="$emit('submit')">
            <h2>Student Registration</h2>
            <div class="two">
                <label>Name <input v-model="registerForm.name" required></label>
                <label>Email <input v-model="registerForm.email" type="email" required></label>
                <label>Password <input v-model="registerForm.password" type="password" required></label>
                <label>Phone <input v-model="registerForm.phone"></label>
                <label>Date of birth <input v-model="registerForm.dob" type="date"></label>
                <label>Gender <input v-model="registerForm.gender"></label>
            </div>
            <label>Address <input v-model="registerForm.address"></label>
            <label>Father name <input v-model="registerForm.fatname"></label>
            <button class="legacy-primary" :disabled="busy">{{ busy ? 'Creating...' : 'Register' }}</button>
        </form>
    </section>
</template>
