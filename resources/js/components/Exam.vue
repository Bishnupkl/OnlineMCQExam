<script setup>
defineProps({
    exam: { type: Object, required: true },
    answers: { type: Object, required: true },
    remaining: { type: Number, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits(['submit']);
</script>

<template>
    <section class="exam2-page">
        <div class="exam2-row">
            <div class="exam2-side"></div>
            <div class="exam2-paper">
                <h1><center><b>Online Entrance Examination <br>{{ new Date().getFullYear() }}</b></center></h1>
                <p class="exam2-marks"><b>Full Marks: 20 <br> Pass Marks:8 <br> Time: 45 min</b></p><br>
                <p><i>Candidates are higly restricted to bring external devices such as mobile, pendrive, <br>external disks etc and They should click on only one botton.</i></p>
                <b>Attempt all questions</b>
                <span class="exam2-time">
                    <input type="text" :value="Math.floor(remaining / 60)" disabled>
                    <input class="seconds" type="text" :value="remaining % 60" disabled>
                </span>
                <span class="exam2-time-label">Remaining time</span><br><br>

                <form class="exam2-form" @submit.prevent="$emit('submit')">
                    <div v-for="(question, index) in exam.questions" :key="question.q_id" class="exam2-question">
                        {{ index + 1 }}){{ question.question }}
                        <br>
                        <template v-for="(choice, choiceIndex) in question.choices" :key="choice">
                            <input
                                v-model="answers[question.q_id]"
                                type="radio"
                                :id="`q-${question.q_id}-${choiceIndex}`"
                                :name="`q${question.q_id}`"
                                :value="choice"
                            >
                            <label :for="`q-${question.q_id}-${choiceIndex}`">{{ choice }}</label>
                        </template>
                        <br><br>
                    </div>
                    <input type="submit" value="submit" class="btn-primary exam2-submit" :disabled="busy">
                    <br><br>
                </form>
            </div>
            <div class="exam2-side"></div>
        </div>
    </section>
</template>
