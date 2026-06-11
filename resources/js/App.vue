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
const busy = ref(false);
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
const attemptedCount = computed(() => Object.values(answers).filter(Boolean).length);
const examProgress = computed(() => {
    if (!exam.value?.questions?.length) {
        return 0;
    }

    return Math.round((attemptedCount.value / exam.value.questions.length) * 100);
});
const resultStatusClass = computed(() => dashboard.value?.result?.status?.toLowerCase() === 'passed' ? 'passed' : 'failed');
const latestNotice = computed(() => notices.value[0] ?? null);
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

async function runAction(action) {
    busy.value = true;
    message.value = '';

    try {
        await action();
    } catch (error) {
        setMessage(error.message || 'Something went wrong.');
    } finally {
        busy.value = false;
    }
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
                <p class="eyebrow">Laravel 13 + Vue 3</p>
                <h1>Online Entrance Examination</h1>
            </div>
            <nav v-if="user" class="nav">
                <button :class="{ active: view === 'dashboard' }" @click="view = 'dashboard'">Dashboard</button>
                <button v-if="isStaff" :class="{ active: view === 'admin' }" @click="view = 'admin'">Manage</button>
                <button :class="{ active: view === 'notices' }" @click="view = 'notices'">Notices</button>
                <button class="ghost" :disabled="busy" @click="runAction(logout)">Logout</button>
            </nav>
        </section>

        <p v-if="message" class="toast">{{ message }}</p>
        <p v-if="loading" class="panel loading-state">Loading application...</p>

        <section v-else-if="!user" class="auth-grid">
            <div class="intro-panel">
                <p class="eyebrow">Modern exam workspace</p>
                <h2>Run entrance exams, publish notices, and score MCQs from one clean dashboard.</h2>
                <div class="feature-rail">
                    <span>Session auth</span>
                    <span>Timed exams</span>
                    <span>Auto scoring</span>
                    <span>Admin controls</span>
                </div>
            </div>

            <form class="panel auth-panel" @submit.prevent="runAction(login)">
                <div class="form-heading">
                    <h2>Sign in</h2>
                    <span>{{ loginForm.role }}</span>
                </div>
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
                <button class="primary" :disabled="busy">{{ busy ? 'Working...' : 'Sign in' }}</button>
            </form>

            <form class="panel auth-panel register-panel" @submit.prevent="runAction(registerStudent)">
                <div class="form-heading">
                    <h2>Student registration</h2>
                    <span>new</span>
                </div>
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
                <button class="primary" :disabled="busy">{{ busy ? 'Creating...' : 'Create student account' }}</button>
            </form>
        </section>

        <section v-else-if="view === 'dashboard'" class="grid">
            <article class="panel hero-panel">
                <p class="eyebrow">{{ user.role }}</p>
                <h2>{{ user.name }}</h2>
                <p class="hero-copy">Exam date: {{ dashboard?.exam_date ?? 'Not scheduled' }}</p>
                <button v-if="user.role === 'student' && !dashboard?.result" class="primary" :disabled="busy" @click="runAction(startExam)">
                    Start exam
                </button>
                <div v-if="dashboard?.result" class="result-strip" :class="resultStatusClass">
                    <span>{{ dashboard.result.status }}</span>
                    <strong>{{ dashboard.result.mark_obtained }} marks</strong>
                    <small>{{ dashboard.result.right_answer }} right / {{ dashboard.result.wrong_answer }} wrong</small>
                </div>
            </article>
            <article class="panel stat"><span>{{ dashboard?.question_count ?? dashboard?.questions ?? 0 }}</span><small>Questions ready</small></article>
            <article class="panel stat"><span>{{ dashboard?.notice_count ?? 0 }}</span><small>Published notices</small></article>
            <article v-if="isStaff" class="panel stat"><span>{{ dashboard?.students ?? 0 }}</span><small>Registered students</small></article>
            <article class="panel activity-panel">
                <p class="eyebrow">Latest notice</p>
                <h3>{{ latestNotice?.n_heading ?? 'No notice yet' }}</h3>
                <p>{{ latestNotice?.n_text ?? 'Publish one from the manage screen.' }}</p>
                <button class="ghost" @click="view = 'notices'">View notices</button>
            </article>
            <article v-if="isStaff" class="panel activity-panel">
                <p class="eyebrow">Quick action</p>
                <h3>Question bank</h3>
                <p>{{ questions.length }} questions currently available for randomized exams.</p>
                <button class="primary" @click="view = 'admin'">Manage exam</button>
            </article>
        </section>

        <section v-else-if="view === 'exam' && exam" class="exam-layout">
            <aside class="panel timer">
                <p class="eyebrow">Remaining time</p>
                <span>{{ remainingLabel }}</span>
                <div class="progress-shell">
                    <div class="progress-bar" :style="{ width: `${examProgress}%` }"></div>
                </div>
                <small>{{ attemptedCount }} of {{ exam.questions.length }} answered</small>
                <button class="primary" :disabled="busy" @click="runAction(submitExam)">Submit</button>
            </aside>
            <form class="question-list" @submit.prevent="runAction(submitExam)">
                <article v-for="(question, index) in exam.questions" :key="question.q_id" class="panel question">
                    <div class="question-head">
                        <h3>{{ index + 1 }}. {{ question.question }}</h3>
                        <span>{{ question.mark }} mark</span>
                    </div>
                    <label v-for="choice in question.choices" :key="choice" class="choice" :class="{ selected: answers[question.q_id] === choice }">
                        <input v-model="answers[question.q_id]" type="radio" :name="`q-${question.q_id}`" :value="choice">
                        {{ choice }}
                    </label>
                </article>
            </form>
        </section>

        <section v-else-if="view === 'admin'" class="admin-layout">
            <form class="panel admin-card" @submit.prevent="runAction(saveQuestion)">
                <div class="form-heading">
                    <h2>Add question</h2>
                    <span>{{ questions.length }} total</span>
                </div>
                <label>Question <textarea v-model="questionForm.question" required></textarea></label>
                <div class="two">
                    <label>Choice 1 <input v-model="questionForm.choice1" required></label>
                    <label>Choice 2 <input v-model="questionForm.choice2" required></label>
                    <label>Choice 3 <input v-model="questionForm.choice3" required></label>
                    <label>Choice 4 <input v-model="questionForm.choice4" required></label>
                    <label>Correct answer <input v-model="questionForm.correct_ans" required></label>
                    <label>Mark <input v-model.number="questionForm.mark" type="number" min="0" step="0.25"></label>
                </div>
                <button class="primary" :disabled="busy">Save question</button>
            </form>
            <form class="panel admin-card" @submit.prevent="runAction(saveNotice)">
                <h2>Publish notice</h2>
                <label>Heading <input v-model="noticeForm.n_heading" required></label>
                <label>Short text <input v-model="noticeForm.n_text"></label>
                <label>Description <textarea v-model="noticeForm.n_description"></textarea></label>
                <button class="primary" :disabled="busy">Publish</button>
            </form>
            <form v-if="user.role === 'admin'" class="panel compact-form" @submit.prevent="runAction(saveExamDate)">
                <h2>Exam date</h2>
                <input v-model="examDateForm.edate" type="date" required>
                <button class="primary" :disabled="busy">Update date</button>
            </form>
            <article class="panel question-table">
                <h2>Question bank</h2>
                <p v-if="!questions.length" class="empty-state">No questions have been added yet.</p>
                <div v-for="question in questions" :key="question.q_id" class="row">
                    <span>{{ question.question }}</span>
                    <button class="danger" :disabled="busy" @click="runAction(() => deleteQuestion(question.q_id))">Delete</button>
                </div>
            </article>
            <article class="panel question-table" v-if="dashboard?.results?.length">
                <h2>Recent results</h2>
                <div v-for="result in dashboard.results" :key="result.id" class="result-row">
                    <span>{{ result.email }}</span>
                    <strong>{{ result.mark_obtained }}</strong>
                    <small>{{ result.status }}</small>
                </div>
            </article>
        </section>

        <section v-else class="notice-list">
            <p v-if="!notices.length" class="panel empty-state">No notices published yet.</p>
            <article v-for="notice in notices" :key="notice.n_id" class="panel notice">
                <p class="eyebrow">{{ notice.n_date }}</p>
                <h2>{{ notice.n_heading }}</h2>
                <strong>{{ notice.n_text }}</strong>
                <p>{{ notice.n_description }}</p>
            </article>
        </section>
    </main>
</template>
