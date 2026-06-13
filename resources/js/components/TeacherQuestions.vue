<script setup>
defineProps({
    questionForm: { type: Object, required: true },
    questions: { type: Array, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits(['submit']);
</script>

<template>
    <section class="legacy-container teacher-question-page">
        <article class="panel teacher-question-card">
            <p class="eyebrow">Teacher panel</p>
            <h2>Add exam question</h2>
            <p>Add MCQ questions from the frontend. The question will be added to the exam question bank immediately.</p>
            <form class="teacher-question-form" @submit.prevent="$emit('submit')">
                <label>Question <textarea v-model="questionForm.question" required></textarea></label>
                <div class="two">
                    <label>Choice 1 <input v-model="questionForm.choice1" required></label>
                    <label>Choice 2 <input v-model="questionForm.choice2" required></label>
                    <label>Choice 3 <input v-model="questionForm.choice3" required></label>
                    <label>Choice 4 <input v-model="questionForm.choice4" required></label>
                    <label>Correct answer <input v-model="questionForm.correct_ans" required></label>
                    <label>Mark <input v-model.number="questionForm.mark" type="number" min="0" step="0.25"></label>
                </div>
                <button class="legacy-primary" type="submit" :disabled="busy">{{ busy ? 'Saving...' : 'Save question' }}</button>
            </form>
        </article>
        <article class="panel teacher-question-card">
            <p class="eyebrow">Current bank</p>
            <h3>{{ questions.length }} questions available</h3>
            <p>Use the admin panel only for full management. Teachers can add new questions here without entering the backend panel.</p>
        </article>
    </section>
</template>
