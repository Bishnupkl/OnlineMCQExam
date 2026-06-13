<script setup>
defineProps({
    activeSlide: { type: Object, required: true },
    slides: { type: Array, required: true },
    carouselIndex: { type: Number, required: true },
    dashboard: { type: Object, default: null },
    publicStats: { type: Object, required: true },
});

defineEmits(['previous-slide', 'next-slide']);
</script>

<template>
    <section class="legacy-home">
        <section class="legacy-banner" :style="{ backgroundImage: `linear-gradient(rgba(23, 22, 23, .2), rgba(23, 22, 23, .5)), url(${activeSlide.image})` }">
            <button class="carousel-arrow left" @click="$emit('previous-slide')">&lt;</button>
            <h2>{{ activeSlide.title }}</h2>
            <button class="carousel-arrow right" @click="$emit('next-slide')">&gt;</button>
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
</template>
