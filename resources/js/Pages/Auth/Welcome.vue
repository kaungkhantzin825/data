<template>
    <v-app theme="pipelineTheme">
        <div class="page-root">
            <div class="bg-fill"></div>
            <div v-if="!smAndDown" class="desktop">
                <div class="d-left">
                    <img src="/images/logo_web.png" alt="Pipeline" class="logo" />
                </div>
                <div class="d-right">
                    <div class="d-box">
                        <h1 class="d-title">Join Us!</h1>
                        <p class="d-sub">To begin this journey, tell us what type of account you'd be opening.</p>

                        <div class="opt-card" @click="goToLogin"
                            @mouseenter="h1=true" @mouseleave="h1=false"
                            :class="{hov: h1}">
                            <div class="opt-square-fill">
                                <v-icon icon="mdi-format-align-left" size="22" color="#fff" />
                            </div>
                            <div class="opt-body">
                                <div class="opt-title">Sign-In</div>
                                <div class="opt-sub">Login to our site</div>
                            </div>
                            <v-icon icon="mdi-arrow-right" size="22"
                                color="#2ecc5e" class="opt-arrow" />
                        </div>

                       
                        <div class="opt-card" @click="goToRegister"
                            @mouseenter="h2=true" @mouseleave="h2=false"
                            :class="{hov: h2}">
                            <div class="opt-square-outline">
                                <v-icon icon="mdi-web" size="22" color="#2ecc5e" />
                            </div>
                            <div class="opt-body">
                                <div class="opt-title">Create Account</div>
                                <div class="opt-sub">Own or belong to a company, this is for you.</div>
                            </div>
                           
                        </div>
                    </div>
                </div>

            </div>

         
            <div v-if="smAndDown" class="mobile">

                <div class="m-top">
                    <div class="m-overlay"></div>
                    <img src="/images/logo_web.png" alt="Pipeline" class="m-logo" />
                </div>

                <div class="m-card">
                    <h1 class="m-title">Join Us!</h1>
                    <p class="m-sub">To begin this journey, tell us what type of account you'd be opening.</p>

                    <div class="opt-card m-opt" @click="goToLogin">
                        <div class="opt-square-fill">
                            <v-icon icon="mdi-format-align-left" size="20" color="#fff" />
                        </div>
                        <div class="opt-body">
                            <div class="opt-title">Sign-In</div>
                            <div class="opt-sub">Login to our site</div>
                        </div>
                        <v-icon icon="mdi-arrow-right" size="20" color="#2ecc5e" />
                    </div>

                    <div class="opt-card m-opt" @click="goToRegister">
                        <div class="opt-square-outline">
                            <v-icon icon="mdi-web" size="20" color="#2ecc5e" />
                        </div>
                        <div class="opt-body">
                            <div class="opt-title">Create Account</div>
                            <div class="opt-sub">Own or belong to a company, this is for you.</div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { useDisplay } from 'vuetify';
import { router } from '@inertiajs/vue3';

const { smAndDown } = useDisplay();
const h1 = ref(false);
const h2 = ref(false);
const goToLogin    = () => router.visit('/login');
const goToRegister = () => router.visit('/register');
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:deep(.v-application__wrap) {
    min-height: unset !important;
    height: 100% !important;
}


.page-root {
    position: fixed;
    inset: 0;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
}

.bg-fill {
    position: absolute;
    inset: 0;
    background-image: url('/images/Login-First page.png');
    background-size: 100% 100%;
    background-position: center;
    background-repeat: no-repeat;
    z-index: 0;
}


.desktop {
    position: absolute;
    inset: 0;
    z-index: 1;
    display: flex;
    flex-direction: row;
}


.d-left {
    flex: 0 0 45%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo {
    width: clamp(150px, 16vw, 240px);
    height: auto;
    object-fit: contain;
    transition: opacity .2s;
}


.d-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 8%;
    padding-right: 5%;
}

.d-box {
    width: 100%;
    max-width: 440px; 
}

.d-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 10px;
    line-height: 1.15;
    letter-spacing: -0.5px;
}

.d-sub {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.78);
    margin: 0 0 24px;
    line-height: 1.5;
}

.opt-card {
    display: flex;
    align-items: center;
    gap: 16px;
    width: 100%;
    background: #fdfdfd; 
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px 20px;
    margin-top: 14px;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.opt-card:first-of-type { margin-top: 0; }

.opt-card.hov {
    transform: translateY(-2px);
    border-color: #2ecc5e;
    box-shadow: 0 8px 30px rgba(46,204,94,0.15);
}


.opt-square-fill {
    flex-shrink: 0;
    width: 46px;
    height: 46px;
    background: #2ecc5e;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 2px; 
}

.opt-square-outline {
    flex-shrink: 0;
    width: 46px;
    height: 46px;
    background: transparent;
    border: 1.5px solid #2ecc5e;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 2px; 
}

.opt-body { flex: 1; min-width: 0; }

.opt-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0d2418;
    line-height: 1.25;
}

.opt-sub {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 4px;
    line-height: 1.4;
}

.opt-arrow { 
    flex-shrink: 0; 
    transition: transform .15s ease;
}
.opt-card.hov .opt-arrow { 
    transform: translateX(4px); 
}


.mobile {
    position: absolute;
    inset: 0;
    z-index: 1;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}


.m-top {
    position: relative;
    flex: 0 0 25%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #0b1410;
}
.m-overlay {
    position: absolute;
    inset: 0;
    background: rgba(4, 10, 7, 0.90);
}
.m-logo {
    position: relative;
    z-index: 1;
    width: 170px;
    height: auto;
    object-fit: contain;
}

.m-card {
    flex: 1;
    background: #fff;
    margin-top: 0px;
    position: relative;
    z-index: 2;
    padding: 32px 24px 40px;
}
.m-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 6px;
}
.m-sub {
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 24px;
    line-height: 1.5;
}
.m-opt {
    margin-top: 12px;
}
</style>
