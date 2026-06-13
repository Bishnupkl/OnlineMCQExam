<script setup>
defineProps({
    user: { type: Object, required: true },
    dashboard: { type: Object, default: null },
    resultStatusClass: { type: String, required: true },
    isStaff: { type: Boolean, required: true },
    latestNotice: { type: Object, default: null },
    questions: { type: Array, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits(['start-exam', 'view-notices', 'manage-questions', 'add-question']);
</script>

<template>
    <section class="legacy-container grid">
        <article class="panel hero-panel">
            <p class="eyebrow">{{ user.role }}</p>
            <h2>{{ user.name }}</h2>
            <p class="hero-copy">Exam date: {{ dashboard?.exam_date ?? 'Not scheduled' }}</p>
            <button v-if="user.role === 'student' && !dashboard?.exam_taken && !dashboard?.result" class="primary" :disabled="busy" @click="$emit('start-exam')">
                Start exam
            </button>
            <div v-if="dashboard?.result" class="result-strip" :class="resultStatusClass">
                <span>{{ dashboard.result.status }}</span>
                <strong>{{ dashboard.result.mark_obtained }} marks</strong>
                <small>{{ dashboard.result.right_answer }} right / {{ dashboard.result.wrong_answer }} wrong</small>
            </div>
            <p v-else-if="user.role === 'student' && dashboard?.exam_taken" class="hero-copy">
                Exam submitted. Result will be visible after admin publishes it.
            </p>
        </article>
        <article class="panel stat"><span>{{ dashboard?.question_count ?? dashboard?.questions ?? 0 }}</span><small>Questions ready</small></article>
        <article class="panel stat"><span>{{ dashboard?.notice_count ?? 0 }}</span><small>Published notices</small></article>
        <article v-if="isStaff" class="panel stat"><span>{{ dashboard?.students ?? 0 }}</span><small>Registered students</small></article>
        <article class="panel activity-panel">
            <p class="eyebrow">Latest notice</p>
            <h3>{{ latestNotice?.n_heading ?? 'No notice yet' }}</h3>
            <p>{{ latestNotice?.n_text ?? 'Publish one from the manage screen.' }}</p>
            <button class="ghost" @click="$emit('view-notices')">View notices</button>
        </article>
        <article v-if="isStaff" class="panel activity-panel">
            <p class="eyebrow">Quick action</p>
            <h3>Question bank</h3>
            <p>{{ questions.length }} questions currently available for randomized exams.</p>
            <button v-if="user.role === 'admin'" class="primary" @click="$emit('manage-questions')">Manage exam</button>
            <button v-else class="primary" @click="$emit('add-question')">Add question</button>
        </article>
    </section>
</template>
