<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import AdminPanel from './components/AdminPanel.vue';
import Dashboard from './components/Dashboard.vue';
import Exam from './components/Exam.vue';
import ExamCompleted from './components/ExamCompleted.vue';
import Home from './components/Home.vue';
import Login from './components/Login.vue';
import Notices from './components/Notices.vue';
import Register from './components/Register.vue';
import TeacherQuestions from './components/TeacherQuestions.vue';

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

function previousSlide() {
    carouselIndex.value = (carouselIndex.value + slides.length - 1) % slides.length;
}

function nextSlide() {
    carouselIndex.value = (carouselIndex.value + 1) % slides.length;
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

        <Home
            v-else-if="view === 'home'"
            :active-slide="activeSlide"
            :slides="slides"
            :carousel-index="carouselIndex"
            :dashboard="dashboard"
            :public-stats="publicStats"
            @previous-slide="previousSlide"
            @next-slide="nextSlide"
        />

        <Login
            v-else-if="view === 'login'"
            :login-form="loginForm"
            :busy="busy"
            @submit="runAction(login)"
        />

        <Register
            v-else-if="view === 'register'"
            :register-form="registerForm"
            :busy="busy"
            @submit="runAction(registerStudent)"
        />

        <Dashboard
            v-else-if="view === 'dashboard'"
            :user="user"
            :dashboard="dashboard"
            :result-status-class="resultStatusClass"
            :is-staff="isStaff"
            :latest-notice="latestNotice"
            :questions="questions"
            :busy="busy"
            @start-exam="runAction(startExam)"
            @view-notices="setView('notices', '/notice')"
            @manage-questions="setAdminSection('questions')"
            @add-question="setView('teacher-questions', '/teacher/questions')"
        />

        <ExamCompleted
            v-else-if="view === 'exam-completed'"
            :result-label="completedResultLabel()"
            @view-notices="setView('notices', '/notice')"
        />

        <TeacherQuestions
            v-else-if="view === 'teacher-questions'"
            :question-form="questionForm"
            :questions="questions"
            :busy="busy"
            @submit="runAction(saveQuestion)"
        />

        <Exam
            v-else-if="view === 'exam' && exam"
            :exam="exam"
            :answers="answers"
            :remaining="remaining"
            :busy="busy"
            @submit="runAction(submitExam)"
        />

        <AdminPanel
            v-else-if="view === 'admin'"
            :admin-section="adminSection"
            :dashboard="dashboard"
            :exam-date-form="examDateForm"
            :result-date-form="resultDateForm"
            :teacher-form="teacherForm"
            :student-form="studentForm"
            :question-form="questionForm"
            :result-form="resultForm"
            :notice-form="noticeForm"
            :editing="editing"
            :questions="questions"
            :notices="notices"
            :busy="busy"
            @logout="runAction(logout)"
            @set-admin-section="setAdminSection"
            @save-exam-date="runAction(saveExamDate)"
            @save-result-date="runAction(saveResultDate)"
            @save-teacher="runAction(saveTeacher)"
            @reset-teacher-form="resetTeacherForm"
            @edit-teacher="editTeacher"
            @save-student="runAction(saveStudent)"
            @reset-student-form="resetStudentForm"
            @edit-student="editStudent"
            @save-question="runAction(saveQuestion)"
            @reset-question-form="resetQuestionForm"
            @edit-question="editQuestion"
            @delete-question="(id) => runAction(() => deleteQuestion(id))"
            @save-result="runAction(saveResult)"
            @reset-result-form="resetResultForm"
            @edit-result="editResult"
            @save-notice="runAction(saveNotice)"
            @reset-notice-form="resetNoticeForm"
            @edit-notice="editNotice"
        />

        <Notices v-else :notices="notices" />

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
