<script setup>
defineProps({
    resultLabel: { type: String, required: true },
});

defineEmits(['view-notices']);
</script>

<template>
    <section class="legacy-container completed-page">
        <article class="panel completed-card">
            <p class="eyebrow">Exam completed</p>
            <h2>Your exam has already been submitted.</h2>
            <p>You will get the notification when the result gets published.</p>
            <p>{{ resultLabel }}</p>
            <button class="primary" @click="$emit('view-notices')">View notices</button>
        </article>
    </section>
</template>
