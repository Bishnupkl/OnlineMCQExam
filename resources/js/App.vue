<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';

const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const user = ref(null);
const loading = ref(true);
const message = ref('');
const messageType = ref('success');
const view = ref('home');
const adminSection = ref('dashboard');
const currentPath = ref(window.location.pathname);
const notices = ref([]);
const dashboard = ref(null);
const publicStats = ref({ students: 0, teachers: 0, questions: 0, notices: 0 });
const questions = ref([]);
const exam = ref(null);
const answers = reactive({});
const remaining = ref(45 * 60);
const busy = ref(false);
const carouselIndex = ref(0);
let timer = null;
let carouselTimer = null;

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
const studentForm = reactive({
    name: '',
    address: '',
    fatname: '',
    dob: '',
    phone: '',
    email: '',
    password: '',
    gender: '',
    exam_status: 'not taken',
});
const teacherForm = reactive({
    t_name: '',
    t_gender: '',
    t_address: '',
    t_phone: '',
    t_email: '',
    t_password: '',
    subject: '',
    permission: '',
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
const resultForm = reactive({
    email: '',
    ques_attempted: 0,
    mark_obtained: 0,
    right_answer: 0,
    wrong_answer: 0,
    status: 'not taken',
});
const examDateForm = reactive({ edate: '' });
const resultDateForm = reactive({ rdate: '' });
const editing = reactive({
    student: null,
    teacher: null,
    question: null,
    result: null,
    notice: null,
});

const isStaff = computed(() => ['admin', 'teacher'].includes(user.value?.role));
const showPublicChrome = computed(() => currentPath.value === '/' || !['exam', 'admin'].includes(view.value));
const attemptedCount = computed(() => Object.values(answers).filter(Boolean).length);
const examProgress = computed(() => {
    if (!exam.value?.questions?.length) {
        return 0;
    }

    return Math.round((attemptedCount.value / exam.value.questions.length) * 100);
});
const resultStatusClass = computed(() => dashboard.value?.result?.status?.toLowerCase() === 'passed' ? 'passed' : 'failed');
const latestNotice = computed(() => notices.value[0] ?? null);
const slides = [
    { title: 'Take entrance exam online', image: '/legacy-assets/banner1.jpg' },
    { title: 'Secure environment', image: '/legacy-assets/banner2.jpg' },
    { title: 'Result will be published soon', image: '/legacy-assets/banner3.jpg' },
    { title: 'No cheating', image: '/legacy-assets/banner4.jpg' },
];
const activeSlide = computed(() => slides[carouselIndex.value]);
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

function viewForPath(pathname) {
    if (pathname.startsWith('/admin')) {
        return 'admin';
    }

    return {
        '/login': 'login',
        '/register': 'register',
        '/notice': 'notices',
        '/result': 'dashboard',
        '/exam': 'exam',
        '/exam-completed': 'exam-completed',
        '/teacher/questions': 'teacher-questions',
    }[pathname] ?? 'home';
}

function setView(nextView, path = null) {
    view.value = nextView;
    if (path && window.location.pathname !== path) {
        window.history.pushState({}, '', path);
    }
    currentPath.value = window.location.pathname;
}

function adminSectionForPath(pathname) {
    const section = pathname.replace(/^\/admin\/?/, '');
    return ['students', 'teachers', 'questions', 'result', 'notice'].includes(section) ? section : 'dashboard';
}

function setAdminSection(section) {
    adminSection.value = section;
    setView('admin', section === 'dashboard' ? '/admin' : `/admin/${section}`);
}

function goToResult() {
    if (user.value?.role === 'student' && dashboard.value?.exam_taken && !dashboard.value?.result) {
        setView('exam-completed', '/exam-completed');
        return;
    }

    setView('dashboard', '/result');
}

function completedResultLabel() {
    return dashboard.value?.result_date
        ? `Result publish date: ${String(dashboard.value.result_date).slice(0, 10)}`
        : 'You will get a notification when the result gets published.';
}

function setMessage(text, type = 'success') {
    message.value = text;
    messageType.value = type;
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
        setMessage(error.message || 'Something went wrong.', 'error');
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

async function loadPublicStats() {
    publicStats.value = await api('/api/public-stats');
}

async function login() {
    const data = await api('/api/login', { method: 'POST', body: loginForm });
    user.value = data.user;
    if (loginForm.role === 'admin') {
        setAdminSection(adminSection.value);
    } else if (loginForm.role === 'teacher') {
        setView('teacher-questions', '/teacher/questions');
    } else {
        setView('dashboard', '/result');
    }
    await Promise.all([refreshDashboard(), loadNotices()]);
    if (user.value?.role === 'student' && dashboard.value?.exam_taken && !dashboard.value?.result) {
        setView('exam-completed', '/exam-completed');
    }
}

async function registerStudent() {
    const data = await api('/api/students', { method: 'POST', body: registerForm });
    user.value = data.user;
    setView('dashboard', '/result');
    await Promise.all([refreshDashboard(), loadNotices()]);
}

async function logout() {
    await api('/api/logout', { method: 'POST' });
    user.value = null;
    dashboard.value = null;
    exam.value = null;
    questions.value = [];
    setView('home', '/');
    stopTimer();
}

async function startExam() {
    const data = await api('/api/exam');
    exam.value = data;
    Object.keys(answers).forEach((key) => delete answers[key]);
    remaining.value = data.duration_minutes * 60;
    setView('exam', '/exam');
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
    dashboard.value = {
        ...(dashboard.value ?? {}),
        result: result.result,
        result_published: result.result_published,
        exam_taken: true,
    };
    exam.value = null;
    setView(result.result ? 'dashboard' : 'exam-completed', result.result ? '/result' : '/exam-completed');
    setMessage(result.message ?? 'Exam submitted. Result will be visible after admin publishes it.');
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
    resultDateForm.rdate = overview.result_date ?? '';
}

async function saveQuestion() {
    const wasEditing = Boolean(editing.question);
    if (wasEditing) {
        await api(`/api/admin/questions/${editing.question}`, { method: 'PUT', body: questionForm });
    } else {
        await api('/api/admin/questions', { method: 'POST', body: questionForm });
    }
    resetQuestionForm();
    await loadAdmin();
    setMessage(wasEditing ? 'Question updated successfully.' : 'Question saved successfully.');
}

async function saveStudent() {
    const wasEditing = Boolean(editing.student);
    if (wasEditing) {
        await api(`/api/admin/students/${editing.student}`, { method: 'PUT', body: studentForm });
    } else {
        await api('/api/admin/students', { method: 'POST', body: studentForm });
    }
    resetStudentForm();
    await loadAdmin();
    setMessage(wasEditing ? 'Student updated successfully.' : 'Student saved successfully.');
}

async function saveTeacher() {
    const wasEditing = Boolean(editing.teacher);
    if (wasEditing) {
        await api(`/api/admin/teachers/${editing.teacher}`, { method: 'PUT', body: teacherForm });
    } else {
        await api('/api/admin/teachers', { method: 'POST', body: teacherForm });
    }
    resetTeacherForm();
    await loadAdmin();
    setMessage(wasEditing ? 'Teacher updated successfully.' : 'Teacher saved successfully.');
}

async function saveResult() {
    const wasEditing = Boolean(editing.result);
    if (wasEditing) {
        await api(`/api/admin/results/${editing.result}`, { method: 'PUT', body: resultForm });
    } else {
        await api('/api/admin/results', { method: 'POST', body: resultForm });
    }
    resetResultForm();
    await loadAdmin();
    setMessage(wasEditing ? 'Result updated successfully.' : 'Result saved successfully.');
}

async function deleteQuestion(id) {
    await api(`/api/admin/questions/${id}`, { method: 'DELETE' });
    await loadAdmin();
}

async function saveNotice() {
    const wasEditing = Boolean(editing.notice);
    if (wasEditing) {
        await api(`/api/admin/notices/${editing.notice}`, { method: 'PUT', body: noticeForm });
    } else {
        await api('/api/admin/notices', { method: 'POST', body: noticeForm });
    }
    resetNoticeForm();
    await loadNotices();
    setMessage(wasEditing ? 'Notice updated successfully.' : 'Notice saved successfully.');
}

async function saveExamDate() {
    await api('/api/admin/exam-date', { method: 'POST', body: examDateForm });
    await refreshDashboard();
    setMessage('Exam date updated.');
}

async function saveResultDate() {
    await api('/api/admin/result-date', { method: 'POST', body: resultDateForm });
    await refreshDashboard();
    setMessage('Result publish date updated.');
}

function resetStudentForm() {
    editing.student = null;
    Object.assign(studentForm, {
        name: '',
        address: '',
        fatname: '',
        dob: '',
        phone: '',
        email: '',
        password: '',
        gender: '',
        exam_status: 'not taken',
    });
}

function editStudent(student) {
    editing.student = student.id;
    Object.assign(studentForm, {
        name: student.name ?? '',
        address: student.address ?? '',
        fatname: student.fatname ?? '',
        dob: student.dob ? String(student.dob).slice(0, 10) : '',
        phone: student.phone ?? '',
        email: student.email ?? '',
        password: '',
        gender: student.gender ?? '',
        exam_status: student.exam_status ?? 'not taken',
    });
}

function resetTeacherForm() {
    editing.teacher = null;
    Object.assign(teacherForm, {
        t_name: '',
        t_gender: '',
        t_address: '',
        t_phone: '',
        t_email: '',
        t_password: '',
        subject: '',
        permission: '',
    });
}

function editTeacher(teacher) {
    editing.teacher = teacher.t_id;
    Object.assign(teacherForm, {
        t_name: teacher.t_name ?? '',
        t_gender: teacher.t_gender ?? '',
        t_address: teacher.t_address ?? '',
        t_phone: teacher.t_phone ?? '',
        t_email: teacher.t_email ?? '',
        t_password: '',
        subject: teacher.subject ?? '',
        permission: teacher.permission ?? '',
    });
}

function resetQuestionForm() {
    editing.question = null;
    Object.assign(questionForm, {
        question: '',
        choice1: '',
        choice2: '',
        choice3: '',
        choice4: '',
        correct_ans: '',
        mark: 1,
    });
}

function editQuestion(question) {
    editing.question = question.q_id;
    Object.assign(questionForm, {
        question: question.question ?? '',
        choice1: question.choice1 ?? '',
        choice2: question.choice2 ?? '',
        choice3: question.choice3 ?? '',
        choice4: question.choice4 ?? '',
        correct_ans: question.correct_ans ?? '',
        mark: question.mark ?? 1,
    });
}

function resetResultForm() {
    editing.result = null;
    Object.assign(resultForm, {
        email: '',
        ques_attempted: 0,
        mark_obtained: 0,
        right_answer: 0,
        wrong_answer: 0,
        status: 'not taken',
    });
}

function editResult(result) {
    editing.result = result.id;
    Object.assign(resultForm, {
        email: result.email ?? '',
        ques_attempted: result.ques_attempted ?? 0,
        mark_obtained: result.mark_obtained ?? 0,
        right_answer: result.right_answer ?? 0,
        wrong_answer: result.wrong_answer ?? 0,
        status: result.status ?? 'not taken',
    });
}

function resetNoticeForm() {
    editing.notice = null;
    Object.assign(noticeForm, { n_heading: '', n_text: '', n_description: '' });
}

function editNotice(notice) {
    editing.notice = notice.n_id;
    Object.assign(noticeForm, {
        n_heading: notice.n_heading ?? '',
        n_text: notice.n_text ?? '',
        n_description: notice.n_description ?? '',
    });
}

onMounted(async () => {
    window.addEventListener('popstate', syncViewFromPath);
    carouselTimer = window.setInterval(() => {
        carouselIndex.value = (carouselIndex.value + 1) % slides.length;
    }, 4500);

    try {
        const requestedView = viewForPath(window.location.pathname);
        view.value = requestedView;
        if (requestedView === 'admin') {
            loginForm.role = 'admin';
            adminSection.value = adminSectionForPath(window.location.pathname);
        }

        const session = await api('/api/session');
        user.value = session.user;
        await Promise.all([loadNotices(), loadPublicStats()]);
        if (user.value) {
            await refreshDashboard();
            if (requestedView === 'admin') {
                view.value = user.value.role === 'admin' ? 'admin' : 'teacher-questions';
                if (user.value.role === 'teacher') {
                    window.history.replaceState({}, '', '/teacher/questions');
                    currentPath.value = window.location.pathname;
                }
            } else if (requestedView === 'exam' && user.value.role === 'student' && dashboard.value?.exam_taken && !dashboard.value?.result) {
                setView('exam-completed', '/exam-completed');
            } else if (requestedView === 'exam' && user.value.role === 'student' && !dashboard.value?.exam_taken) {
                await startExam();
            } else if (requestedView === 'dashboard' && user.value.role === 'student' && dashboard.value?.exam_taken && !dashboard.value?.result) {
                setView('exam-completed', '/exam-completed');
            } else if (requestedView === 'exam-completed' && user.value.role === 'student' && dashboard.value?.exam_taken && !dashboard.value?.result) {
                view.value = 'exam-completed';
            } else if (requestedView === 'teacher-questions' && user.value.role === 'teacher') {
                view.value = 'teacher-questions';
            } else if (['home', 'notices', 'dashboard'].includes(requestedView)) {
                view.value = requestedView;
            } else {
                view.value = 'dashboard';
            }
        } else if (['admin', 'exam', 'exam-completed', 'teacher-questions'].includes(requestedView)) {
            view.value = 'login';
        }
    } finally {
        loading.value = false;
    }
});

onUnmounted(() => {
    window.removeEventListener('popstate', syncViewFromPath);
    stopTimer();
    if (carouselTimer) {
        window.clearInterval(carouselTimer);
    }
});

function syncViewFromPath() {
    currentPath.value = window.location.pathname;
    const nextView = viewForPath(currentPath.value);
    view.value = nextView;
    if (nextView === 'admin') {
        adminSection.value = adminSectionForPath(currentPath.value);
    }
}
</script>

<template>
    <main>
        <header v-if="showPublicChrome" class="legacy-header" id="home">
            <nav class="legacy-navbar">
                <button class="brand-button" @click="setView('home', '/')">
                    <img :src="'/legacy-assets/logo.png'" alt="Online Entrance Examination">
                </button>
                <ul class="legacy-nav">
                    <li><button :class="{ active: view === 'home' }" @click="setView('home', '/')">Home</button></li>
                    <li v-if="!user"><button :class="{ active: view === 'login' }" @click="setView('login', '/login')">LogIn</button></li>
                    <li v-if="!user" class="dropdown-shell">
                        <button :class="{ active: view === 'register' }" @click="setView('register', '/register')">Register</button>
                        <div class="dropdown-menu">
                            <button @click="setView('register', '/register')">Student</button>
                            <button @click="setView('register', '/register')">Teacher</button>
                        </div>
                    </li>
                    <li><button :class="{ active: view === 'notices' }" @click="setView('notices', '/notice')">Notice</button></li>
                    <li v-if="user"><button :class="{ active: view === 'dashboard' || view === 'exam-completed' }" @click="goToResult">Result</button></li>
                    <li v-if="user?.role === 'teacher'"><button :class="{ active: view === 'teacher-questions' }" @click="setView('teacher-questions', '/teacher/questions')">Add Question</button></li>
                    <li v-if="user?.role === 'admin'"><button :class="{ active: view === 'admin' }" @click="setView('admin', '/admin')">Manage</button></li>
                    <li><a href="#contact">Contact</a></li>
                    <li v-if="user"><button :disabled="busy" @click="runAction(logout)">Logout</button></li>
                </ul>
                <form class="legacy-search" @submit.prevent>
                    <input type="search" placeholder="Search here...">
                    <button>Search</button>
                </form>
            </nav>
        </header>

        <p v-if="message" class="toast" :class="messageType">{{ message }}</p>
        <p v-if="loading" class="legacy-container panel loading-state">Loading application...</p>

        <section v-else-if="view === 'home'" class="legacy-home">
            <section class="legacy-banner" :style="{ backgroundImage: `linear-gradient(rgba(23, 22, 23, .2), rgba(23, 22, 23, .5)), url(${activeSlide.image})` }">
                <button class="carousel-arrow left" @click="carouselIndex = (carouselIndex + slides.length - 1) % slides.length">&lt;</button>
                <h2>{{ activeSlide.title }}</h2>
                <button class="carousel-arrow right" @click="carouselIndex = (carouselIndex + 1) % slides.length">&gt;</button>
            </section>

            <section class="legacy-about" id="about">
                <div class="legacy-container about-grid">
                    <h3 class="title">Interior<span> About</span></h3>
                    <img :src="'/legacy-assets/about2.png'" alt="Online examination illustration">
                    <div class="about-copy">
                        <h4>Online Entrance Examination is a web based system which allows a particular company or educational institute to arrange, conduct and manage examination via online in a secure environment. This system is <span>Multiple Choice Questions (MCQ)</span> based examination system that provides user friendly environment for both exam Conductors and Students appearing for Examination. The main objective of this system is to provide examination environment in a secure way.Security is ensured by website blocking mechanism.</h4>
                    </div>
                </div>
            </section>

            <section class="legacy-stats">
                <article class="counter-grid"><span>SE</span><p>55</p><h4>Seats</h4></article>
                <article class="counter-grid1"><span>TE</span><p>{{ dashboard?.teachers ?? publicStats.teachers }}</p><h4>Teachers</h4></article>
                <article class="counter-grid2"><span>SU</span><p>4</p><h4>Subjects</h4></article>
                <article class="counter-grid3"><span>ST</span><p>{{ dashboard?.students ?? publicStats.students }}</p><h4>Students</h4></article>
            </section>

            <section class="legacy-team" id="contact">
                <h3>Team Members</h3>
                <div class="team-card">
                    <img :src="'/legacy-assets/bishnu.jpg'" alt="Bishnu Pokhrel">
                    <h4>Bishnu Pokhrel</h4>
                    <i>Nepal</i>
                    <p>Frontend and Backend developer</p>
                </div>
            </section>
        </section>

        <section v-else-if="view === 'login'" class="legacy-container form-page">
            <form class="legacy-form" @submit.prevent="runAction(login)">
                <h2>LogIn</h2>
                <label>Role
                    <select v-model="loginForm.role">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
                <label>Email <input v-model="loginForm.email" type="email" required></label>
                <label>Password <input v-model="loginForm.password" type="password" required></label>
                <button class="legacy-primary" :disabled="busy">{{ busy ? 'Working...' : 'LogIn' }}</button>
            </form>
        </section>

        <section v-else-if="view === 'register'" class="legacy-container form-page">
            <form class="legacy-form wide" @submit.prevent="runAction(registerStudent)">
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

        <section v-else-if="view === 'dashboard'" class="legacy-container grid">
            <article class="panel hero-panel">
                <p class="eyebrow">{{ user.role }}</p>
                <h2>{{ user.name }}</h2>
                <p class="hero-copy">Exam date: {{ dashboard?.exam_date ?? 'Not scheduled' }}</p>
                <button v-if="user.role === 'student' && !dashboard?.exam_taken && !dashboard?.result" class="primary" :disabled="busy" @click="runAction(startExam)">
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
                <button class="ghost" @click="view = 'notices'">View notices</button>
            </article>
            <article v-if="isStaff" class="panel activity-panel">
                <p class="eyebrow">Quick action</p>
                <h3>Question bank</h3>
                <p>{{ questions.length }} questions currently available for randomized exams.</p>
                <button v-if="user.role === 'admin'" class="primary" @click="setAdminSection('questions')">Manage exam</button>
                <button v-else class="primary" @click="setView('teacher-questions', '/teacher/questions')">Add question</button>
            </article>
        </section>

        <section v-else-if="view === 'exam-completed'" class="legacy-container completed-page">
            <article class="panel completed-card">
                <p class="eyebrow">Exam completed</p>
                <h2>Your exam has already been submitted.</h2>
                <p>You will get the notification when the result gets published.</p>
                <p>{{ completedResultLabel() }}</p>
                <button class="primary" @click="setView('notices', '/notice')">View notices</button>
            </article>
        </section>

        <section v-else-if="view === 'teacher-questions'" class="legacy-container teacher-question-page">
            <article class="panel teacher-question-card">
                <p class="eyebrow">Teacher panel</p>
                <h2>Add exam question</h2>
                <p>Add MCQ questions from the frontend. The question will be added to the exam question bank immediately.</p>
                <form class="teacher-question-form" @submit.prevent="runAction(saveQuestion)">
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

        <section v-else-if="view === 'exam' && exam" class="exam2-page">
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

                    <form class="exam2-form" @submit.prevent="runAction(submitExam)">
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

        <section v-else-if="view === 'admin'" class="sb-admin-page">
            <nav class="sb-topbar">
                <a class="sb-brand"><i><b>Online Entrance Examination</b></i></a>
                <div class="vertical-line"></div>
                <div class="sb-search">
                    <input type="text" placeholder="Search for...">
                    <button type="button">Search</button>
                </div>
                <button class="sb-logout" :disabled="busy" @click="runAction(logout)">Logout</button>
            </nav>

            <aside class="sb-sidebar">
                <button :class="{ active: adminSection === 'dashboard' }" @click="setAdminSection('dashboard')">Dashboard</button>
                <button :class="{ active: adminSection === 'students' }" @click="setAdminSection('students')">Students</button>
                <button :class="{ active: adminSection === 'teachers' }" @click="setAdminSection('teachers')">Teachers</button>
                <button :class="{ active: adminSection === 'questions' }" @click="setAdminSection('questions')">Questions</button>
                <button :class="{ active: adminSection === 'result' }" @click="setAdminSection('result')">Result</button>
                <button :class="{ active: adminSection === 'notice' }" @click="setAdminSection('notice')">Notice</button>
                <button class="sb-collapse">&lt;</button>
            </aside>

            <div class="content-wrapper">
                <div v-if="adminSection === 'dashboard'" class="admin-stats-row">
                    <div class="admin-stat seat-box">
                        <div class="admin-icon">seat</div>
                        <p><span>55</span>Seats</p>
                    </div>
                    <div class="admin-stat student-box">
                        <div class="admin-icon">student</div>
                        <p><span>{{ dashboard?.students ?? 0 }}</span>Students</p>
                    </div>
                    <div class="admin-stat subject-box">
                        <div class="admin-icon">subject</div>
                        <p><span>4</span>Subjects</p>
                    </div>
                    <div class="admin-stat teacher-box">
                        <div class="admin-icon">teacher</div>
                        <p><span>{{ dashboard?.teachers ?? 0 }}</span>Teachers</p>
                    </div>
                </div>

                <div v-if="adminSection === 'dashboard'" class="admin-date-row">
                    <form class="admin-date-card" @submit.prevent="runAction(saveExamDate)">
                        <h2>Set Examination Date</h2>
                        <input v-model="examDateForm.edate" type="date" required>
                        <button type="submit" :disabled="busy">Set Exam Date</button>
                    </form>
                    <form class="admin-date-card right" @submit.prevent="runAction(saveResultDate)">
                        <h2>Set Result Date</h2>
                        <input v-model="resultDateForm.rdate" type="date" required>
                        <button type="submit" :disabled="busy">Publish Result</button>
                        <p>{{ dashboard?.result_published ? 'Result is visible to students.' : 'Result is hidden from students.' }}</p>
                    </form>
                </div>

                <div v-if="adminSection === 'dashboard' || adminSection === 'teachers'" class="admin-table-block">
                    <h3>Grant permission to the teachers</h3>
                    <form v-if="adminSection === 'teachers'" class="admin-manage-card admin-section-form" @submit.prevent="runAction(saveTeacher)">
                        <h2>{{ editing.teacher ? 'Edit teacher' : 'Add teacher' }}</h2>
                        <div class="two">
                            <label>Name <input v-model="teacherForm.t_name" required></label>
                            <label>Email <input v-model="teacherForm.t_email" type="email" required></label>
                            <label>Password <input v-model="teacherForm.t_password" type="password" :required="!editing.teacher"></label>
                            <label>Subject <input v-model="teacherForm.subject"></label>
                            <label>Gender <input v-model="teacherForm.t_gender"></label>
                            <label>Phone <input v-model="teacherForm.t_phone"></label>
                            <label>Address <input v-model="teacherForm.t_address"></label>
                            <label>Permission <input v-model="teacherForm.permission"></label>
                        </div>
                        <button type="submit" :disabled="busy">{{ editing.teacher ? 'Update teacher' : 'Save teacher' }}</button>
                        <button v-if="editing.teacher" type="button" class="admin-cancel" @click="resetTeacherForm">Cancel edit</button>
                    </form>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <td>SN</td>
                                <td>Name</td>
                                <td>Subject</td>
                                <td>Gender</td>
                                <td>Address</td>
                                <td>Phone No</td>
                                <td>Email</td>
                                <td>Permission</td>
                                <td>Action</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(teacher, index) in dashboard?.teacher_rows ?? []" :key="teacher.t_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ teacher.t_name }}</td>
                                <td>{{ teacher.subject }}</td>
                                <td>{{ teacher.t_gender }}</td>
                                <td>{{ teacher.t_address }}</td>
                                <td>{{ teacher.t_phone }}</td>
                                <td>{{ teacher.t_email }}</td>
                                <td><button type="button">Grant</button></td>
                                <td><button type="button" @click="editTeacher(teacher)">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'students'" class="admin-table-block">
                    <h3>Students</h3>
                    <form class="admin-manage-card admin-section-form" @submit.prevent="runAction(saveStudent)">
                        <h2>{{ editing.student ? 'Edit student' : 'Add student' }}</h2>
                        <div class="two">
                            <label>Name <input v-model="studentForm.name" required></label>
                            <label>Email <input v-model="studentForm.email" type="email" required></label>
                            <label>Password <input v-model="studentForm.password" type="password" :required="!editing.student"></label>
                            <label>Phone <input v-model="studentForm.phone"></label>
                            <label>Date of birth <input v-model="studentForm.dob" type="date"></label>
                            <label>Gender <input v-model="studentForm.gender"></label>
                            <label>Address <input v-model="studentForm.address"></label>
                            <label>Father Name <input v-model="studentForm.fatname"></label>
                            <label>Exam Status
                                <select v-model="studentForm.exam_status">
                                    <option value="not taken">not taken</option>
                                    <option value="taken">taken</option>
                                </select>
                            </label>
                        </div>
                        <button type="submit" :disabled="busy">{{ editing.student ? 'Update student' : 'Save student' }}</button>
                        <button v-if="editing.student" type="button" class="admin-cancel" @click="resetStudentForm">Cancel edit</button>
                    </form>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <td>SN</td>
                                <td>Name</td>
                                <td>Address</td>
                                <td>Father Name</td>
                                <td>DOB</td>
                                <td>Phone</td>
                                <td>Email</td>
                                <td>Gender</td>
                                <td>Exam Status</td>
                                <td>Action</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(student, index) in dashboard?.student_rows ?? []" :key="student.id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ student.name }}</td>
                                <td>{{ student.address }}</td>
                                <td>{{ student.fatname }}</td>
                                <td>{{ student.dob }}</td>
                                <td>{{ student.phone }}</td>
                                <td>{{ student.email }}</td>
                                <td>{{ student.gender }}</td>
                                <td>{{ student.exam_status }}</td>
                                <td><button type="button" @click="editStudent(student)">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'questions'" class="admin-table-block">
                    <h3>Questions</h3>
                    <form class="admin-manage-card admin-section-form" @submit.prevent="runAction(saveQuestion)">
                        <h2>{{ editing.question ? 'Edit question' : 'Add question' }}</h2>
                        <label>Question <textarea v-model="questionForm.question" required></textarea></label>
                        <div class="two">
                            <label>Choice 1 <input v-model="questionForm.choice1" required></label>
                            <label>Choice 2 <input v-model="questionForm.choice2" required></label>
                            <label>Choice 3 <input v-model="questionForm.choice3" required></label>
                            <label>Choice 4 <input v-model="questionForm.choice4" required></label>
                            <label>Correct answer <input v-model="questionForm.correct_ans" required></label>
                            <label>Mark <input v-model.number="questionForm.mark" type="number" min="0" step="0.25"></label>
                        </div>
                        <button type="submit" :disabled="busy">{{ editing.question ? 'Update question' : 'Save question' }}</button>
                        <button v-if="editing.question" type="button" class="admin-cancel" @click="resetQuestionForm">Cancel edit</button>
                    </form>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <td>SN</td>
                                <td>Question</td>
                                <td>Choice 1</td>
                                <td>Choice 2</td>
                                <td>Choice 3</td>
                                <td>Choice 4</td>
                                <td>Correct</td>
                                <td>Mark</td>
                                <td>Action</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(question, index) in questions" :key="question.q_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ question.question }}</td>
                                <td>{{ question.choice1 }}</td>
                                <td>{{ question.choice2 }}</td>
                                <td>{{ question.choice3 }}</td>
                                <td>{{ question.choice4 }}</td>
                                <td>{{ question.correct_ans }}</td>
                                <td>{{ question.mark }}</td>
                                <td>
                                    <button type="button" @click="editQuestion(question)">Edit</button>
                                    <button type="button" :disabled="busy" @click="runAction(() => deleteQuestion(question.q_id))">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'result'" class="admin-table-block">
                    <h3>Result</h3>
                    <form class="admin-manage-card admin-section-form" @submit.prevent="runAction(saveResult)">
                        <h2>{{ editing.result ? 'Edit result' : 'Add result' }}</h2>
                        <div class="two">
                            <label>Email <input v-model="resultForm.email" type="email" required></label>
                            <label>Attempted <input v-model.number="resultForm.ques_attempted" type="number" min="0" required></label>
                            <label>Mark <input v-model.number="resultForm.mark_obtained" type="number" step="0.25" required></label>
                            <label>Right Answer <input v-model.number="resultForm.right_answer" type="number" min="0" required></label>
                            <label>Wrong Answer <input v-model.number="resultForm.wrong_answer" type="number" min="0" required></label>
                            <label>Status
                                <select v-model="resultForm.status" required>
                                    <option value="not taken">not taken</option>
                                    <option value="Passed">Passed</option>
                                    <option value="Failed">Failed</option>
                                </select>
                            </label>
                        </div>
                        <button type="submit" :disabled="busy">{{ editing.result ? 'Update result' : 'Save result' }}</button>
                        <button v-if="editing.result" type="button" class="admin-cancel" @click="resetResultForm">Cancel edit</button>
                    </form>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <td>SN</td>
                                <td>Email</td>
                                <td>Attempted</td>
                                <td>Mark</td>
                                <td>Right</td>
                                <td>Wrong</td>
                                <td>Status</td>
                                <td>Action</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(result, index) in dashboard?.results ?? []" :key="result.id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ result.email }}</td>
                                <td>{{ result.ques_attempted }}</td>
                                <td>{{ result.mark_obtained }}</td>
                                <td>{{ result.right_answer }}</td>
                                <td>{{ result.wrong_answer }}</td>
                                <td>{{ result.status }}</td>
                                <td><button type="button" @click="editResult(result)">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'notice'" class="admin-table-block">
                    <h3>Notice</h3>
                    <form class="admin-manage-card admin-section-form" @submit.prevent="runAction(saveNotice)">
                        <h2>{{ editing.notice ? 'Edit notice' : 'Add notice' }}</h2>
                        <label>Heading <input v-model="noticeForm.n_heading" required></label>
                        <label>Short text <input v-model="noticeForm.n_text"></label>
                        <label>Description <textarea v-model="noticeForm.n_description"></textarea></label>
                        <button type="submit" :disabled="busy">{{ editing.notice ? 'Update notice' : 'Save notice' }}</button>
                        <button v-if="editing.notice" type="button" class="admin-cancel" @click="resetNoticeForm">Cancel edit</button>
                    </form>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <td>SN</td>
                                <td>Date</td>
                                <td>Heading</td>
                                <td>Text</td>
                                <td>Description</td>
                                <td>Action</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(notice, index) in notices" :key="notice.n_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ notice.n_date }}</td>
                                <td>{{ notice.n_heading }}</td>
                                <td>{{ notice.n_text }}</td>
                                <td>{{ notice.n_description }}</td>
                                <td><button type="button" @click="editNotice(notice)">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'dashboard'" class="admin-forms-row">
                    <form class="admin-manage-card" @submit.prevent="runAction(saveQuestion)">
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
                        <button type="submit" :disabled="busy">Save question</button>
                    </form>
                    <form class="admin-manage-card" @submit.prevent="runAction(saveNotice)">
                        <h2>Publish notice</h2>
                        <label>Heading <input v-model="noticeForm.n_heading" required></label>
                        <label>Short text <input v-model="noticeForm.n_text"></label>
                        <label>Description <textarea v-model="noticeForm.n_description"></textarea></label>
                        <button type="submit" :disabled="busy">Publish</button>
                    </form>
                </div>
            </div>
        </section>

        <section v-else class="legacy-container notice-list">
            <p v-if="!notices.length" class="panel empty-state">No notices published yet.</p>
            <article v-for="notice in notices" :key="notice.n_id" class="panel notice">
                <p class="eyebrow">{{ notice.n_date }}</p>
                <h2>{{ notice.n_heading }}</h2>
                <strong>{{ notice.n_text }}</strong>
                <p>{{ notice.n_description }}</p>
            </article>
        </section>

        <footer v-if="showPublicChrome" class="legacy-footer">
            <div class="legacy-container footer-grid">
                <div class="footer-social">
                    <h4>Follow us on:</h4>
                    <span>f</span>
                    <span>t</span>
                    <span>g+</span>
                    <span>p</span>
                </div>
                <p>© {{ new Date().getFullYear() }} Online Entrance. All Rights Reserved | Design by <a href="#">Bishnu Pokhrel</a></p>
            </div>
        </footer>
    </main>
</template>
