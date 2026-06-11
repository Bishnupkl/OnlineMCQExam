<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const user = ref(null);
const loading = ref(true);
const message = ref('');
const view = ref('dashboard');
const notices = ref([]);
const dashboard = ref(null);
const questions = ref([]);
const exam = ref(null);
const answers = reactive({});
const remaining = ref(45 * 60);
let timer = null;

const loginForm = reactive({ role: 'student', email: '', password: '' });
const registerForm = reactive({
    name: '',
    address: '',
    fatname: '',
    dob: '',
    phone: '',
    email: '',
    password: '',
    gender: '',
});
const questionForm = reactive({
    question: '',
    choice1: '',
    choice2: '',
    choice3: '',
    choice4: '',
    correct_ans: '',
    mark: 1,
});
const noticeForm = reactive({ n_heading: '', n_text: '', n_description: '' });
const examDateForm = reactive({ edate: '' });

const isStaff = computed(() => ['admin', 'teacher'].includes(user.value?.role));
const remainingLabel = computed(() => {
    const minutes = Math.floor(remaining.value / 60).toString().padStart(2, '0');
    const seconds = (remaining.value % 60).toString().padStart(2, '0');
    return `${minutes}:${seconds}`;
});

async function api(path, options = {}) {
    const response = await fetch(path, {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            ...(options.headers ?? {}),
        },
        credentials: 'same-origin',
        ...options,
        body: options.body ? JSON.stringify(options.body) : undefined,
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.message ?? 'Request failed.');
    }

    return data;
}

function setMessage(text) {
    message.value = text;
    window.setTimeout(() => {
        if (message.value === text) {
            message.value = '';
        }
    }, 4000);
}

async function refreshDashboard() {
    dashboard.value = await api('/api/dashboard');
    if (isStaff.value) {
        await loadAdmin();
    }
}

async function loadNotices() {
    notices.value = (await api('/api/notices')).notices;
}

async function login() {
    const data = await api('/api/login', { method: 'POST', body: loginForm });
    user.value = data.user;
    view.value = 'dashboard';
    await Promise.all([refreshDashboard(), loadNotices()]);
}

async function registerStudent() {
    const data = await api('/api/students', { method: 'POST', body: registerForm });
    user.value = data.user;
    view.value = 'dashboard';
    await Promise.all([refreshDashboard(), loadNotices()]);
}

async function logout() {
    await api('/api/logout', { method: 'POST' });
    user.value = null;
    dashboard.value = null;
    exam.value = null;
    questions.value = [];
    view.value = 'dashboard';
    stopTimer();
}

async function startExam() {
    const data = await api('/api/exam');
    exam.value = data;
    Object.keys(answers).forEach((key) => delete answers[key]);
    remaining.value = data.duration_minutes * 60;
    view.value = 'exam';
    stopTimer();
    timer = window.setInterval(() => {
        remaining.value -= 1;
        if (remaining.value <= 0) {
            submitExam();
        }
    }, 1000);
}

async function submitExam() {
    stopTimer();
    const result = await api('/api/exam/submit', {
        method: 'POST',
        body: { answers },
    });
    dashboard.value = { ...(dashboard.value ?? {}), result: result.result };
    exam.value = null;
    view.value = 'dashboard';
    setMessage('Exam submitted. Result saved.');
}

function stopTimer() {
    if (timer) {
        window.clearInterval(timer);
        timer = null;
    }
}

async function loadAdmin() {
    const [overview, questionData] = await Promise.all([
        api('/api/admin/overview'),
        api('/api/admin/questions'),
    ]);
    dashboard.value = { ...(dashboard.value ?? {}), ...overview };
    questions.value = questionData.questions;
    examDateForm.edate = overview.exam_date ?? '';
}

async function saveQuestion() {
    await api('/api/admin/questions', { method: 'POST', body: questionForm });
    Object.assign(questionForm, {
        question: '',
        choice1: '',
        choice2: '',
        choice3: '',
        choice4: '',
        correct_ans: '',
        mark: 1,
    });
    await loadAdmin();
    setMessage('Question saved.');
}

async function deleteQuestion(id) {
    await api(`/api/admin/questions/${id}`, { method: 'DELETE' });
    await loadAdmin();
}

async function saveNotice() {
    await api('/api/admin/notices', { method: 'POST', body: noticeForm });
    Object.assign(noticeForm, { n_heading: '', n_text: '', n_description: '' });
    await loadNotices();
    setMessage('Notice published.');
}

async function saveExamDate() {
    await api('/api/admin/exam-date', { method: 'POST', body: examDateForm });
    await refreshDashboard();
    setMessage('Exam date updated.');
}

onMounted(async () => {
    try {
        const session = await api('/api/session');
        user.value = session.user;
        await loadNotices();
        if (user.value) {
            await refreshDashboard();
        }
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <main class="shell">
        <section class="topbar">
            <div>
                <p class="eyebrow">OEE Migration</p>
                <h1>Online Entrance Examination</h1>
            </div>
            <nav v-if="user" class="nav">
                <button :class="{ active: view === 'dashboard' }" @click="view = 'dashboard'">Dashboard</button>
                <button v-if="isStaff" :class="{ active: view === 'admin' }" @click="view = 'admin'">Manage</button>
                <button :class="{ active: view === 'notices' }" @click="view = 'notices'">Notices</button>
                <button class="ghost" @click="logout">Logout</button>
            </nav>
        </section>

        <p v-if="message" class="toast">{{ message }}</p>
        <p v-if="loading" class="panel">Loading application...</p>

        <section v-else-if="!user" class="auth-grid">
            <form class="panel auth-panel" @submit.prevent="login">
                <h2>Sign in</h2>
                <label>
                    Role
                    <select v-model="loginForm.role">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
                <label>
                    Email
                    <input v-model="loginForm.email" type="email" required>
                </label>
                <label>
                    Password
                    <input v-model="loginForm.password" type="password" required>
                </label>
                <button class="primary">Sign in</button>
            </form>

            <form class="panel auth-panel" @submit.prevent="registerStudent">
                <h2>Student registration</h2>
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
                <button class="primary">Create student account</button>
            </form>
        </section>

        <section v-else-if="view === 'dashboard'" class="grid">
            <article class="panel hero-panel">
                <p class="eyebrow">{{ user.role }}</p>
                <h2>{{ user.name }}</h2>
                <p>Exam date: {{ dashboard?.exam_date ?? 'Not scheduled' }}</p>
                <button v-if="user.role === 'student' && !dashboard?.result" class="primary" @click="startExam">
                    Start exam
                </button>
                <div v-if="dashboard?.result" class="result-strip">
                    <span>{{ dashboard.result.status }}</span>
                    <strong>{{ dashboard.result.mark_obtained }} marks</strong>
                    <small>{{ dashboard.result.right_answer }} right / {{ dashboard.result.wrong_answer }} wrong</small>
                </div>
            </article>
            <article class="panel stat"><span>{{ dashboard?.question_count ?? dashboard?.questions ?? 0 }}</span> Questions</article>
            <article class="panel stat"><span>{{ dashboard?.notice_count ?? 0 }}</span> Notices</article>
            <article v-if="isStaff" class="panel stat"><span>{{ dashboard?.students ?? 0 }}</span> Students</article>
        </section>

        <section v-else-if="view === 'exam' && exam" class="exam-layout">
            <aside class="panel timer">
                <span>{{ remainingLabel }}</span>
                <button class="primary" @click="submitExam">Submit</button>
            </aside>
            <form class="question-list" @submit.prevent="submitExam">
                <article v-for="(question, index) in exam.questions" :key="question.q_id" class="panel question">
                    <h3>{{ index + 1 }}. {{ question.question }}</h3>
                    <label v-for="choice in question.choices" :key="choice" class="choice">
                        <input v-model="answers[question.q_id]" type="radio" :name="`q-${question.q_id}`" :value="choice">
                        {{ choice }}
                    </label>
                </article>
            </form>
        </section>

        <section v-else-if="view === 'admin'" class="admin-layout">
            <form class="panel" @submit.prevent="saveQuestion">
                <h2>Add question</h2>
                <label>Question <textarea v-model="questionForm.question" required></textarea></label>
                <div class="two">
                    <label>Choice 1 <input v-model="questionForm.choice1" required></label>
                    <label>Choice 2 <input v-model="questionForm.choice2" required></label>
                    <label>Choice 3 <input v-model="questionForm.choice3" required></label>
                    <label>Choice 4 <input v-model="questionForm.choice4" required></label>
                    <label>Correct answer <input v-model="questionForm.correct_ans" required></label>
                    <label>Mark <input v-model.number="questionForm.mark" type="number" min="0" step="0.25"></label>
                </div>
                <button class="primary">Save question</button>
            </form>
            <form class="panel" @submit.prevent="saveNotice">
                <h2>Publish notice</h2>
                <label>Heading <input v-model="noticeForm.n_heading" required></label>
                <label>Short text <input v-model="noticeForm.n_text"></label>
                <label>Description <textarea v-model="noticeForm.n_description"></textarea></label>
                <button class="primary">Publish</button>
            </form>
            <form v-if="user.role === 'admin'" class="panel compact-form" @submit.prevent="saveExamDate">
                <h2>Exam date</h2>
                <input v-model="examDateForm.edate" type="date" required>
                <button class="primary">Update date</button>
            </form>
            <article class="panel question-table">
                <h2>Question bank</h2>
                <div v-for="question in questions" :key="question.q_id" class="row">
                    <span>{{ question.question }}</span>
                    <button class="danger" @click="deleteQuestion(question.q_id)">Delete</button>
                </div>
            </article>
        </section>

        <section v-else class="notice-list">
            <article v-for="notice in notices" :key="notice.n_id" class="panel notice">
                <p class="eyebrow">{{ notice.n_date }}</p>
                <h2>{{ notice.n_heading }}</h2>
                <strong>{{ notice.n_text }}</strong>
                <p>{{ notice.n_description }}</p>
            </article>
        </section>
    </main>
</template>
