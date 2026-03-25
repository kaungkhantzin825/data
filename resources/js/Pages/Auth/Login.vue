<template>
    <v-app theme="pipelineTheme">
        <div class="page-root">

            <div class="bg-fill"></div>

            <div v-if="!smAndDown" class="desktop">

                <div class="d-left">
                    <img src="/images/logo_web.png" alt="Pipeline" class="logo"
                        @click="router.visit('/')" style="cursor:pointer" />
                </div>

                <div class="d-right">
                    <div class="d-box">
                        <h1 class="d-title">Sign In to Pipeline</h1>
                        <p class="d-sub">Access your dashboard securely.</p>

                        <form @submit.prevent="submit" class="d-form">
                            <div class="fw">
                                <div class="fbox" :class="{focused: ef, ferr: errors.email}">
                                    <v-icon icon="mdi-email-outline" size="17"
                                        :color="ef ? '#2ecc5e' : '#9ca3af'" />
                                    <input v-model="form.email" type="email"
                                        placeholder="Enter your email" class="fi"
                                        autocomplete="email"
                                        @focus="ef=true" @blur="ef=false" />
                                </div>
                                <span v-if="errors.email" class="emsg">{{ errors.email }}</span>
                            </div>

                            <div class="fw">
                                <div class="fbox" :class="{focused: pf, ferr: errors.password}">
                                    <v-icon icon="mdi-lock-outline" size="17"
                                        :color="pf ? '#2ecc5e' : '#9ca3af'" />
                                    <input v-model="form.password"
                                        :type="showPw ? 'text' : 'password'"
                                        placeholder="Enter your password" class="fi"
                                        autocomplete="current-password"
                                        @focus="pf=true" @blur="pf=false" />
                                    <button type="button" class="eye-btn"
                                        @click="showPw=!showPw" tabindex="-1">
                                        <v-icon
                                            :icon="showPw ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                            size="17" color="#9ca3af" />
                                    </button>
                                </div>
                                <span v-if="errors.password" class="emsg">{{ errors.password }}</span>
                            </div>

                            <div style="text-align:right; margin-top:-4px;">
                                <a href="#" class="forgot">Forgot password?</a>
                            </div>

                            <button type="submit" class="sbtn" :disabled="form.processing">
                                <span v-if="!form.processing">Sign In</span>
                                <span v-else class="dots"><span/><span/><span/></span>
                            </button>
                        </form>

                        <p class="helper">
                            Don't have an account?
                            <a href="#" @click.prevent="router.visit('/register')" class="glink">Create one</a>
                        </p>
                    </div>
                </div>

            </div>

            <div v-if="smAndDown" class="mobile">

                <div class="m-top">
                    <div class="m-overlay"></div>
                    <img src="/images/logo_web.png" alt="Pipeline" class="m-logo"
                        @click="router.visit('/')" style="cursor:pointer" />
                </div>

                <div class="m-card">
                    <h1 class="m-title">Sign-in to Pipeline</h1>
                    <p class="m-sub">Access your dashboard securely.</p>

                    <form @submit.prevent="submit" class="m-form">
                        <div class="fw">
                            <div class="fbox mfbox" :class="{focused: ef}">
                                <v-icon icon="mdi-email-outline" size="17"
                                    :color="ef ? '#2ecc5e' : '#9ca3af'" />
                                <input v-model="form.email" type="email"
                                    placeholder="Enter your email" class="fi"
                                    @focus="ef=true" @blur="ef=false" />
                            </div>
                            <span v-if="errors.email" class="emsg dark">{{ errors.email }}</span>
                        </div>

                        <div class="fw">
                            <div class="fbox mfbox" :class="{focused: pf}">
                                <v-icon icon="mdi-lock-outline" size="17"
                                    :color="pf ? '#2ecc5e' : '#9ca3af'" />
                                <input v-model="form.password"
                                    :type="showPw ? 'text' : 'password'"
                                    placeholder="Enter your password" class="fi"
                                    @focus="pf=true" @blur="pf=false" />
                                <button type="button" class="eye-btn"
                                    @click="showPw=!showPw" tabindex="-1">
                                    <v-icon
                                        :icon="showPw ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                        size="17" color="#9ca3af" />
                                </button>
                            </div>
                            <span v-if="errors.password" class="emsg dark">{{ errors.password }}</span>
                        </div>

                        <button type="submit" class="sbtn" :disabled="form.processing">
                            <span v-if="!form.processing">Sign In</span>
                            <span v-else class="dots"><span/><span/><span/></span>
                        </button>
                    </form>

                    <p class="helper mhelper">
                        Don't have an account?
                        <a href="#" @click.prevent="router.visit('/register')" class="glink">Create one</a>
                    </p>
                    <p style="text-align:center;margin-top:10px;">
                        <a href="#" @click.prevent="router.visit('/')" class="back">← Back</a>
                    </p>
                </div>

            </div>

        </div>
    </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { useDisplay } from 'vuetify';
import { useForm, router } from '@inertiajs/vue3';

defineProps({ errors: { type: Object, default: () => ({}) } });

const { smAndDown } = useDisplay();
const showPw = ref(false);
const ef = ref(false);
const pf = ref(false);

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
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
.logo:hover { opacity: .85; }

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
    max-width: 310px;
}

.d-title {
    font-size: clamp(1.4rem, 2.1vw, 1.85rem);
    font-weight: 700;
    color: #fff;
    margin: 0 0 6px;
    line-height: 1.15;
}

.d-sub {
    font-size: clamp(0.78rem, 0.95vw, 0.875rem);
    color: rgba(255,255,255,0.58);
    margin: 0 0 22px;
}

.d-form {
    display: flex;
    flex-direction: column;
    gap: 13px;
}

.fw { display: flex; flex-direction: column; gap: 5px; }

.fbox {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.97);
    border: 1.5px solid rgba(255,255,255,.15);
    border-radius: 10px;
    padding: 0 13px;
    height: 50px;
    box-shadow: 0 2px 12px rgba(0,0,0,.18);
    transition: border-color .18s, box-shadow .18s;
}

.fbox.focused {
    border-color: #2ecc5e;
    box-shadow: 0 0 0 3px rgba(46,204,94,.14), 0 2px 12px rgba(0,0,0,.15);
}

.fbox.ferr { border-color: #f87171; }

.mfbox {
    background: #fff;
    border: 1px solid #e5e7eb !important;
    box-shadow: none !important;
    border-radius: 8px !important;
}
.mfbox.focused {
    border-color: #2ecc5e !important;
    box-shadow: none !important;
}

.fi {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    font-size: .875rem;
    color: #111827;
    height: 100%;
    font-family: 'Inter', sans-serif;
}
.fi::placeholder { color: #9ca3af; }

.eye-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 3px;
    opacity: .8;
}
.eye-btn:hover { opacity: 1; }

.emsg { font-size: .75rem; color: #f87171; padding-left: 3px; }
.emsg.dark { color: #dc2626; }

.forgot {
    font-size: .77rem;
    color: rgba(255,255,255,.5);
    text-decoration: none;
}
.forgot:hover { color: #2ecc5e; }

.sbtn {
    width: 100%;
    height: 50px;
    border-radius: 30px;
    background: linear-gradient(90deg, #27a54e 0%, #2ecc5e 100%);
    color: #fff;
    font-size: .97rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    margin-top: 4px;
    box-shadow: 0 4px 20px rgba(46,204,94,.38);
    font-family: 'Inter', sans-serif;
    transition: transform .18s, box-shadow .18s;
    letter-spacing: .2px;
}
.sbtn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(46,204,94,.48); }
.sbtn:active:not(:disabled) { transform: translateY(0); }
.sbtn:disabled { opacity: .68; cursor: not-allowed; }

.dots { display: inline-flex; gap: 5px; align-items: center; }
.dots span {
    width: 6px; height: 6px; border-radius: 50%; background: #fff;
    animation: bounce 1.2s ease-in-out infinite;
}
.dots span:nth-child(2) { animation-delay: .2s; }
.dots span:nth-child(3) { animation-delay: .4s; }
@keyframes bounce {
    0%,60%,100% { transform: translateY(0); opacity: .5; }
    30% { transform: translateY(-6px); opacity: 1; }
}

.helper { text-align: center; margin-top: 18px; font-size: .8rem; color: rgba(255,255,255,.45); }
.mhelper { color: #6b7280; }
.glink { color: #2ecc5e; text-decoration: none; font-weight: 600; }
.glink:hover { opacity: .8; }

/* ══ MOBILE ══ */
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
    position: relative;
    z-index: 2;
    padding: 32px 24px 40px;
}

.m-card .sbtn {
    background: #0baa48; 
    box-shadow: none;
    height: 48px;
}

.m-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 5px; }
.m-sub { font-size: .83rem; color: #6b7280; margin-bottom: 22px; }

.m-form { display: flex; flex-direction: column; gap: 12px; }

.back { font-size: .8rem; color: #9ca3af; text-decoration: none; }
.back:hover { color: #2ecc5e; }
</style>
