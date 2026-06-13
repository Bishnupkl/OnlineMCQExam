<script setup>
defineProps({
    registerForm: { type: Object, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits(['submit']);
</script>

<template>
    <section class="auth-page">
        <form class="auth-legacy-form auth-register-form" @submit.prevent="$emit('submit')">
            <h1 class="auth-legacy-title">STUDENT REGISTRATION FORM</h1>
            <div class="auth-legacy-panel">
                <label class="auth-row">
                    <span>Your Name</span>
                    <input v-model="registerForm.name" placeholder="Your Full Name" required>
                </label>
                <label class="auth-row">
                    <span>Address</span>
                    <input v-model="registerForm.address" placeholder="Your Address">
                </label>
                <label class="auth-row">
                    <span>Father Name</span>
                    <input v-model="registerForm.fatname" placeholder="Your Father Name">
                </label>
                <label class="auth-row compact">
                    <span>Date of Birth</span>
                    <input v-model="registerForm.dob" type="date" required>
                </label>
                <label class="auth-row">
                    <span>Phone Number</span>
                    <input v-model="registerForm.phone" placeholder="Phone Number">
                </label>
                <label class="auth-row">
                    <span>Email</span>
                    <input v-model="registerForm.email" type="email" placeholder="Your Email" required>
                </label>
                <label class="auth-row">
                    <span>Password</span>
                    <input v-model="registerForm.password" type="password" placeholder="Your Password" required>
                </label>
                <div class="auth-row auth-radio-row">
                    <span>Gender</span>
                    <div class="auth-radio-options">
                        <label><input v-model="registerForm.gender" type="radio" value="male" required> Male</label>
                        <label><input v-model="registerForm.gender" type="radio" value="female"> Female</label>
                    </div>
                </div>
                <div class="auth-actions">
                    <button type="submit" :disabled="busy">{{ busy ? 'CREATING...' : 'SUBMIT' }}</button>
                </div>
            </div>
        </form>
    </section>
</template>
