<script setup>
defineProps({
    adminSection: { type: String, required: true },
    dashboard: { type: Object, default: null },
    examDateForm: { type: Object, required: true },
    resultDateForm: { type: Object, required: true },
    teacherForm: { type: Object, required: true },
    studentForm: { type: Object, required: true },
    questionForm: { type: Object, required: true },
    resultForm: { type: Object, required: true },
    noticeForm: { type: Object, required: true },
    editing: { type: Object, required: true },
    questions: { type: Array, required: true },
    notices: { type: Array, required: true },
    busy: { type: Boolean, required: true },
});

defineEmits([
    'logout',
    'set-admin-section',
    'save-exam-date',
    'save-result-date',
    'save-teacher',
    'reset-teacher-form',
    'edit-teacher',
    'save-student',
    'reset-student-form',
    'edit-student',
    'save-question',
    'reset-question-form',
    'edit-question',
    'delete-question',
    'save-result',
    'reset-result-form',
    'edit-result',
    'save-notice',
    'reset-notice-form',
    'edit-notice',
]);
</script>

<template>
    <section class="sb-admin-page">
            <nav class="sb-topbar">
                <a class="sb-brand"><i><b>Online Entrance Examination</b></i></a>
                <div class="vertical-line"></div>
                <div class="sb-search">
                    <input type="text" placeholder="Search for...">
                    <button type="button">Search</button>
                </div>
                <button class="sb-logout" :disabled="busy" @click="$emit('logout')">Logout</button>
            </nav>

            <aside class="sb-sidebar">
                <button :class="{ active: adminSection === 'dashboard' }" @click="$emit('set-admin-section', 'dashboard')">Dashboard</button>
                <button :class="{ active: adminSection === 'students' }" @click="$emit('set-admin-section', 'students')">Students</button>
                <button :class="{ active: adminSection === 'teachers' }" @click="$emit('set-admin-section', 'teachers')">Teachers</button>
                <button :class="{ active: adminSection === 'questions' }" @click="$emit('set-admin-section', 'questions')">Questions</button>
                <button :class="{ active: adminSection === 'result' }" @click="$emit('set-admin-section', 'result')">Result</button>
                <button :class="{ active: adminSection === 'notice' }" @click="$emit('set-admin-section', 'notice')">Notice</button>
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
                    <form class="admin-date-card" @submit.prevent="$emit('save-exam-date')">
                        <h2>Set Examination Date</h2>
                        <input v-model="examDateForm.edate" type="date" required>
                        <button type="submit" :disabled="busy">Set Exam Date</button>
                    </form>
                    <form class="admin-date-card right" @submit.prevent="$emit('save-result-date')">
                        <h2>Set Result Date</h2>
                        <input v-model="resultDateForm.rdate" type="date" required>
                        <button type="submit" :disabled="busy">Publish Result</button>
                        <p>{{ dashboard?.result_published ? 'Result is visible to students.' : 'Result is hidden from students.' }}</p>
                    </form>
                </div>

                <div v-if="adminSection === 'dashboard' || adminSection === 'teachers'" class="admin-table-block">
                    <h3>Grant permission to the teachers</h3>
                    <form v-if="adminSection === 'teachers'" class="admin-manage-card admin-section-form" @submit.prevent="$emit('save-teacher')">
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
                        <button v-if="editing.teacher" type="button" class="admin-cancel" @click="$emit('reset-teacher-form')">Cancel edit</button>
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
                                <td><button type="button" @click="$emit('edit-teacher', teacher)">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'students'" class="admin-table-block">
                    <h3>Students</h3>
                    <form class="admin-manage-card admin-section-form" @submit.prevent="$emit('save-student')">
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
                        <button v-if="editing.student" type="button" class="admin-cancel" @click="$emit('reset-student-form')">Cancel edit</button>
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
                                <td><button type="button" @click="$emit('edit-student', student)">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'questions'" class="admin-table-block">
                    <h3>Questions</h3>
                    <form class="admin-manage-card admin-section-form" @submit.prevent="$emit('save-question')">
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
                        <button v-if="editing.question" type="button" class="admin-cancel" @click="$emit('reset-question-form')">Cancel edit</button>
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
                                    <button type="button" @click="$emit('edit-question', question)">Edit</button>
                                    <button type="button" :disabled="busy" @click="$emit('delete-question', question.q_id)">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'result'" class="admin-table-block">
                    <h3>Result</h3>
                    <form class="admin-manage-card admin-section-form" @submit.prevent="$emit('save-result')">
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
                        <button v-if="editing.result" type="button" class="admin-cancel" @click="$emit('reset-result-form')">Cancel edit</button>
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
                                <td><button type="button" @click="$emit('edit-result', result)">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'notice'" class="admin-table-block">
                    <h3>Notice</h3>
                    <form class="admin-manage-card admin-section-form" @submit.prevent="$emit('save-notice')">
                        <h2>{{ editing.notice ? 'Edit notice' : 'Add notice' }}</h2>
                        <label>Heading <input v-model="noticeForm.n_heading" required></label>
                        <label>Short text <input v-model="noticeForm.n_text"></label>
                        <label>Description <textarea v-model="noticeForm.n_description"></textarea></label>
                        <button type="submit" :disabled="busy">{{ editing.notice ? 'Update notice' : 'Save notice' }}</button>
                        <button v-if="editing.notice" type="button" class="admin-cancel" @click="$emit('reset-notice-form')">Cancel edit</button>
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
                                <td><button type="button" @click="$emit('edit-notice', notice)">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adminSection === 'dashboard'" class="admin-forms-row">
                    <form class="admin-manage-card" @submit.prevent="$emit('save-question')">
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
                    <form class="admin-manage-card" @submit.prevent="$emit('save-notice')">
                        <h2>Publish notice</h2>
                        <label>Heading <input v-model="noticeForm.n_heading" required></label>
                        <label>Short text <input v-model="noticeForm.n_text"></label>
                        <label>Description <textarea v-model="noticeForm.n_description"></textarea></label>
                        <button type="submit" :disabled="busy">Publish</button>
                    </form>
                </div>
            </div>
    </section>
</template>
