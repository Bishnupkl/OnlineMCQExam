<script setup>
defineProps({
    notices: { type: Array, required: true },
});
</script>

<template>
    <section class="legacy-container notice-list">
        <p v-if="!notices.length" class="panel empty-state">No notices published yet.</p>
        <article v-for="notice in notices" :key="notice.n_id" class="panel notice">
            <p class="eyebrow">{{ notice.n_date }}</p>
            <h2>{{ notice.n_heading }}</h2>
            <strong>{{ notice.n_text }}</strong>
            <p>{{ notice.n_description }}</p>
        </article>
    </section>
</template>
