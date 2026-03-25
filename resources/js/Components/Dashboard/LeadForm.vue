<template>
    <div class="content-card">
        <div class="card-header pb-2" style="padding: 24px 24px 0 24px;">
            <div>
                <h2 class="card-title" style="color:#2ecc5e;">{{ editingId ? 'Edit Lead' : 'Create New Lead' }}</h2>
                <p class="card-sub">{{ editingId ? 'Update Lead details.' : 'Add lead details and source information.' }}</p>
            </div>
        </div>

        <form @submit.prevent="$emit('submit')" class="create-form" style="padding: 24px;">
            
            <div class="section-panel">
                <div class="section-header">
                    <div class="section-title">Lead Detail</div>
                    <button type="button" class="btn-hide" @click="s1Hide = !s1Hide">{{ s1Hide ? 'Show' : 'Hide' }} <v-icon :icon="s1Hide ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="14"/></button>
                </div>
                <div class="section-body form-grid-2" v-show="!s1Hide">
                    <div class="form-group">
                        <label class="form-label">Business Name <span class="req">*</span></label>
                        <input v-model="form.business_name" type="text" class="form-input" :class="{ 'is-invalid': form.errors.business_name }" />
                        <div v-if="form.errors.business_name" class="error-msg">{{ form.errors.business_name }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name <span class="req">*</span></label>
                        <input v-model="form.first_name" type="text" class="form-input" :class="{ 'is-invalid': form.errors.first_name }" />
                        <div v-if="form.errors.first_name" class="error-msg">{{ form.errors.first_name }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name <span class="req">*</span></label>
                        <input v-model="form.last_name" type="text" class="form-input" :class="{ 'is-invalid': form.errors.last_name }" />
                        <div v-if="form.errors.last_name" class="error-msg">{{ form.errors.last_name }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="req">*</span></label>
                        <input v-model="form.contact_email" type="email" class="form-input" :class="{ 'is-invalid': form.errors.contact_email }" />
                        <div v-if="form.errors.contact_email" class="error-msg">{{ form.errors.contact_email }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number <span class="req">*</span></label>
                        <input v-model="form.phone" type="text" class="form-input" :class="{ 'is-invalid': form.errors.phone }" />
                        <div v-if="form.errors.phone" class="error-msg">{{ form.errors.phone }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Secondary Contact Number <span class="req">*</span></label>
                        <input v-model="form.secondary_contact_number" type="text" class="form-input" :class="{ 'is-invalid': form.errors.secondary_contact_number }" />
                        <div v-if="form.errors.secondary_contact_number" class="error-msg">{{ form.errors.secondary_contact_number }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Business Type <span class="req">*</span></label>
                        <div class="sw">
                            <select v-model="form.biz_type" class="fs" :class="{ 'is-invalid': form.errors.biz_type }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.biz_type || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.biz_type" class="error-msg">{{ form.errors.biz_type }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lead Source <span class="req">*</span></label>
                        <div class="sw">
                            <select v-model="form.source" class="fs" :class="{ 'is-invalid': form.errors.source }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.source || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.source" class="error-msg">{{ form.errors.source }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Division <span class="req">*</span></label>
                        <div class="sw">
                            <select v-model="form.division" class="fs" :class="{ 'is-invalid': form.errors.division }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.division || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.division" class="error-msg">{{ form.errors.division }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Township <span class="req">*</span></label>
                        <div class="sw">
                            <select v-model="form.township" class="fs" :class="{ 'is-invalid': form.errors.township }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.township || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.township" class="error-msg">{{ form.errors.township }}</div>
                    </div>
                    <div class="form-group row-full">
                        <label class="form-label">Address <span class="req">*</span></label>
                        <textarea v-model="form.address" class="form-input" rows="3" :class="{ 'is-invalid': form.errors.address }"></textarea>
                        <div v-if="form.errors.address" class="error-msg">{{ form.errors.address }}</div>
                    </div>
                </div>
            </div>

           
            <div class="section-panel">
                <div class="section-header">
                    <div>
                        <div class="section-title">Product</div>
                        <div style="font-size:12px;color:#6b7280;margin-top:4px;">Service package and commercial details.</div>
                    </div>
                    <button type="button" class="btn-hide" @click="s2Hide = !s2Hide">{{ s2Hide ? 'Show' : 'Hide' }} <v-icon :icon="s2Hide ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="14"/></button>
                </div>
                <div class="section-body form-grid-2" v-show="!s2Hide">
                    <div class="form-group">
                        <label class="form-label">Product <span class="req">*</span></label>
                        <div class="sw">
                            <select v-model="form.product" class="fs" :class="{ 'is-invalid': form.errors.product }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.product || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.product" class="error-msg">{{ form.errors.product }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Package <span class="req">*</span></label>
                        <div class="sw">
                            <select v-model="form.package" class="fs" :class="{ 'is-invalid': form.errors.package }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.package || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.package" class="error-msg">{{ form.errors.package }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Package Total <span class="req">*</span></label>
                        <div class="sw">
                            <input type="number" v-model="form.package_total" class="form-input" :class="{ 'is-invalid': form.errors.package_total }" />
                            <v-icon icon="mdi-swap-vertical" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.package_total" class="error-msg">{{ form.errors.package_total }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount <span class="req">*</span></label>
                        <div class="sw">
                            <input type="number" v-model="form.discount" class="form-input" :class="{ 'is-invalid': form.errors.discount }" />
                            <v-icon icon="mdi-swap-vertical" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.discount" class="error-msg">{{ form.errors.discount }}</div>
                    </div>
                    <div class="form-group row-full">
                        <label class="form-label">Note</label>
                        <textarea v-model="form.note" class="form-input" rows="3" :class="{ 'is-invalid': form.errors.note }"></textarea>
                        <div v-if="form.errors.note" class="error-msg">{{ form.errors.note }}</div>
                    </div>
                </div>
            </div>

            <div class="section-panel">
                <div class="section-header">
                    <div class="section-title">Other Information</div>
                    <button type="button" class="btn-hide" @click="s3Hide = !s3Hide">{{ s3Hide ? 'Show' : 'Hide' }} <v-icon :icon="s3Hide ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="14"/></button>
                </div>
                <div class="section-body form-grid-2" v-show="!s3Hide">
                    <div class="form-group">
                        <label class="form-label">Status <span class="req">*</span></label>
                        <div class="sw">
                            <select v-model="form.status" class="fs" :class="{ 'is-invalid': form.errors.status }">
                                <option value="">Select...</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div v-if="form.errors.status" class="error-msg">{{ form.errors.status }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Channel <span class="req">*</span></label>
                        <div class="sw">
                            <select v-model="form.channel" class="fs" :class="{ 'is-invalid': form.errors.channel }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.channel || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.channel" class="error-msg">{{ form.errors.channel }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Installation Appointment</label>
                        <div class="sw-date">
                            <input type="date" v-model="form.installation_appointment" class="fi" :class="{ 'is-invalid': form.errors.installation_appointment }" />
                            <v-icon icon="mdi-calendar-blank-outline" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.installation_appointment" class="error-msg">{{ form.errors.installation_appointment }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Est. Contract Date <span class="req">*</span></label>
                        <div class="sw-date">
                            <input type="date" v-model="form.est_contract_date" class="fi" :class="{ 'is-invalid': form.errors.est_contract_date }" />
                            <v-icon icon="mdi-calendar-blank-outline" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.est_contract_date" class="error-msg">{{ form.errors.est_contract_date }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Est. Start Date <span class="req">*</span></label>
                        <div class="sw-date">
                            <input type="date" v-model="form.est_start_date" class="fi" :class="{ 'is-invalid': form.errors.est_start_date }" />
                            <v-icon icon="mdi-calendar-blank-outline" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.est_start_date" class="error-msg">{{ form.errors.est_start_date }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Est. Follow Up Date <span class="req">*</span></label>
                        <div class="sw-date">
                            <input type="date" v-model="form.est_follow_up_date" class="fi" :class="{ 'is-invalid': form.errors.est_follow_up_date }" />
                            <v-icon icon="mdi-calendar-blank-outline" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.est_follow_up_date" class="error-msg">{{ form.errors.est_follow_up_date }}</div>
                    </div>
                    <div class="form-group row-full mt-2">
                        <label class="form-label">Referral ? <span class="req">*</span></label>
                        <div class="ref-label" @click="form.is_referral = !form.is_referral" style="user-select:none;">
                            <div style="width:16px; height:16px; border-radius:50%; border:1.5px solid #2ecc5e; display:flex; align-items:center; justify-content:center; background:#fff; flex-shrink:0;">
                                <div v-show="form.is_referral" style="width:8px; height:8px; border-radius:50%; background:#2ecc5e;"></div>
                            </div>
                            <span>Is Referral</span>
                        </div>
                        <div v-if="form.errors.is_referral" class="error-msg">{{ form.errors.is_referral }}</div>
                    </div>
                    <div class="form-group row-full mt-2">
                        <label class="form-label">Meeting Note</label>
                        <textarea v-model="form.meeting_note" class="form-input" rows="3" :class="{ 'is-invalid': form.errors.meeting_note }"></textarea>
                        <div v-if="form.errors.meeting_note" class="error-msg">{{ form.errors.meeting_note }}</div>
                    </div>
                    <div class="form-group row-full">
                        <label class="form-label">Next Step</label>
                        <textarea v-model="form.next_step" class="form-input" rows="3" :class="{ 'is-invalid': form.errors.next_step }"></textarea>
                        <div v-if="form.errors.next_step" class="error-msg">{{ form.errors.next_step }}</div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-form-cancel" @click="$emit('cancel')">Cancel</button>
                <button type="submit" class="btn-form-submit">Save Changes</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    form: { type: Object, required: true },
    fieldOptions: { type: Object, default: () => ({}) },
    editingId: { type: [String, Number], default: null }
});

defineEmits(['submit', 'cancel']);

const page = usePage();
const can = (p) => true; 

const s1Hide = ref(false);
const s2Hide = ref(false);
const s3Hide = ref(false);
</script>
