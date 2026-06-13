<script setup>
defineProps({
    questionForm: { type: Object, required: true },
    questions: { type: Array, required: true },
    editingQuestion: { type: Boolean, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits(['submit', 'edit', 'delete', 'reset']);
</script>

<template>
    <section class="legacy-container teacher-question-page">
        <article class="panel teacher-question-card">
            <p class="eyebrow">Teacher panel</p>
            <h2>{{ editingQuestion ? 'Edit exam question' : 'Add exam question' }}</h2>
            <p>{{ editingQuestion ? 'Update your uploaded question from the frontend.' : 'Add MCQ questions from the frontend. The question will be added to the exam question bank immediately.' }}</p>
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
                <div class="teacher-actions">
                    <button class="legacy-primary" type="submit" :disabled="busy">{{ busy ? 'Saving...' : editingQuestion ? 'Update question' : 'Save question' }}</button>
                    <button v-if="editingQuestion" class="ghost" type="button" :disabled="busy" @click="$emit('reset')">Cancel edit</button>
                </div>
            </form>
        </article>
        <article class="panel teacher-question-card">
            <p class="eyebrow">My uploaded questions</p>
            <h3>{{ questions.length }} questions uploaded</h3>
            <p>Only questions uploaded by your teacher account are listed here.</p>
        </article>
        <article class="panel teacher-question-card teacher-question-list">
            <h3>Your question list</h3>
            <div v-if="questions.length" class="teacher-question-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <td>SN</td>
                            <td>Question</td>
                            <td>Correct</td>
                            <td>Mark</td>
                            <td>Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(question, index) in questions" :key="question.q_id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ question.question }}</td>
                            <td>{{ question.correct_ans }}</td>
                            <td>{{ question.mark }}</td>
                            <td>
                                <button type="button" @click="$emit('edit', question)">Edit</button>
                                <button type="button" :disabled="busy" @click="$emit('delete', question.q_id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else>No questions uploaded by you yet.</p>
        </article>
    </section>
</template>
