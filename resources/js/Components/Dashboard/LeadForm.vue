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
                    <div v-if="fields.business_name.is_visible" class="form-group">
                        <label class="form-label">{{ fields.business_name.label }}</label>
                        <input v-model="form.business_name" type="text" class="form-input" :class="{ 'is-invalid': form.errors.business_name }" />
                        <div v-if="form.errors.business_name" class="error-msg">{{ form.errors.business_name }}</div>
                    </div>
                    <div v-if="fields.contact_name.is_visible" class="form-group">
                        <label class="form-label">{{ fields.contact_name.label }}</label>
                        <input v-model="form.first_name" type="text" class="form-input" :class="{ 'is-invalid': form.errors.first_name }" />
                        <div v-if="form.errors.first_name" class="error-msg">{{ form.errors.first_name }}</div>
                    </div>
                    <div v-if="fields.last_name.is_visible" class="form-group">
                        <label class="form-label">{{ fields.last_name.label }}</label>
                        <input v-model="form.last_name" type="text" class="form-input" :class="{ 'is-invalid': form.errors.last_name }" />
                        <div v-if="form.errors.last_name" class="error-msg">{{ form.errors.last_name }}</div>
                    </div>
                    <div v-if="fields.contact_email.is_visible" class="form-group">
                        <label class="form-label">{{ fields.contact_email.label }}</label>
                        <input v-model="form.contact_email" type="email" class="form-input" :class="{ 'is-invalid': form.errors.contact_email }" />
                        <div v-if="form.errors.contact_email" class="error-msg">{{ form.errors.contact_email }}</div>
                    </div>
                    <div v-if="fields.phone.is_visible" class="form-group">
                        <label class="form-label">{{ fields.phone.label }}</label>
                        <input v-model="form.phone" type="text" class="form-input" :class="{ 'is-invalid': form.errors.phone }" />
                        <div v-if="form.errors.phone" class="error-msg">{{ form.errors.phone }}</div>
                    </div>
                    <div v-if="fields.secondary_contact_number.is_visible" class="form-group">
                        <label class="form-label">{{ fields.secondary_contact_number.label }}</label>
                        <input v-model="form.secondary_contact_number" type="text" class="form-input" :class="{ 'is-invalid': form.errors.secondary_contact_number }" />
                        <div v-if="form.errors.secondary_contact_number" class="error-msg">{{ form.errors.secondary_contact_number }}</div>
                    </div>
                    <div v-if="fields.biz_type.is_visible" class="form-group">
                        <label class="form-label">{{ fields.biz_type.label }}</label>
                        <div class="sw">
                            <select v-model="form.biz_type" class="fs" :class="{ 'is-invalid': form.errors.biz_type }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.biz_type || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.biz_type" class="error-msg">{{ form.errors.biz_type }}</div>
                    </div>
                    <div v-if="fields.source.is_visible" class="form-group">
                        <label class="form-label">{{ fields.source.label }}</label>
                        <div class="sw">
                            <select v-model="form.source" class="fs" :class="{ 'is-invalid': form.errors.source }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.source || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.source" class="error-msg">{{ form.errors.source }}</div>
                    </div>
                    <div v-if="fields.division.is_visible" class="form-group">
                        <label class="form-label">{{ fields.division.label }}</label>
                        <div class="sw">
                            <select v-model="form.division" class="fs" :class="{ 'is-invalid': form.errors.division }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.division || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.division" class="error-msg">{{ form.errors.division }}</div>
                    </div>
                    <div v-if="fields.township.is_visible" class="form-group">
                        <label class="form-label">{{ fields.township.label }}</label>
                        <div class="sw">
                            <select v-model="form.township" class="fs" :class="{ 'is-invalid': form.errors.township }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.township || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.township" class="error-msg">{{ form.errors.township }}</div>
                    </div>
                    <div v-if="fields.address.is_visible" class="form-group row-full">
                        <label class="form-label">{{ fields.address.label }}</label>
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
                    <div v-if="fields.product.is_visible" class="form-group">
                        <label class="form-label">{{ fields.product.label }}</label>
                        <div class="sw">
                            <select v-model="form.product" class="fs" :class="{ 'is-invalid': form.errors.product }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.product || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.product" class="error-msg">{{ form.errors.product }}</div>
                    </div>
                    <div v-if="fields.package.is_visible" class="form-group">
                        <label class="form-label">{{ fields.package.label }}</label>
                        <div class="sw">
                            <select v-model="form.package" class="fs" :class="{ 'is-invalid': form.errors.package }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.package || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.package" class="error-msg">{{ form.errors.package }}</div>
                    </div>
                    <div v-if="fields.package_total.is_visible" class="form-group">
                        <label class="form-label">{{ fields.package_total.label }}</label>
                        <div class="sw">
                            <input type="number" v-model="form.package_total" class="form-input" :class="{ 'is-invalid': form.errors.package_total }" />
                            <v-icon icon="mdi-swap-vertical" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.package_total" class="error-msg">{{ form.errors.package_total }}</div>
                    </div>
                    <div v-if="fields.discount.is_visible" class="form-group">
                        <label class="form-label">{{ fields.discount.label }}</label>
                        <div class="sw">
                            <input type="number" v-model="form.discount" class="form-input" :class="{ 'is-invalid': form.errors.discount }" />
                            <v-icon icon="mdi-swap-vertical" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.discount" class="error-msg">{{ form.errors.discount }}</div>
                    </div>
                    <div v-if="fields.note.is_visible" class="form-group row-full">
                        <label class="form-label">{{ fields.note.label }}</label>
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
                    <div v-if="fields.status.is_visible" class="form-group">
                        <label class="form-label">{{ fields.status.label }}</label>
                        <div class="sw">
                            <select v-model="form.status" class="fs" :class="{ 'is-invalid': form.errors.status }">
                                <option value="">Select...</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div v-if="form.errors.status" class="error-msg">{{ form.errors.status }}</div>
                    </div>
                    <div v-if="fields.channel.is_visible" class="form-group">
                        <label class="form-label">{{ fields.channel.label }}</label>
                        <div class="sw">
                            <select v-model="form.channel" class="fs" :class="{ 'is-invalid': form.errors.channel }">
                                <option value="">Select...</option>
                                <option v-for="opt in (fieldOptions?.channel || [])" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.channel" class="error-msg">{{ form.errors.channel }}</div>
                    </div>
                    <div v-if="fields.installation_appointment.is_visible" class="form-group">
                        <label class="form-label">{{ fields.installation_appointment.label }}</label>
                        <div class="sw-date">
                            <input type="date" v-model="form.installation_appointment" class="fi" :class="{ 'is-invalid': form.errors.installation_appointment }" />
                            <v-icon icon="mdi-calendar-blank-outline" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.installation_appointment" class="error-msg">{{ form.errors.installation_appointment }}</div>
                    </div>
                    <div v-if="fields.est_contract_date.is_visible" class="form-group">
                        <label class="form-label">{{ fields.est_contract_date.label }}</label>
                        <div class="sw-date">
                            <input type="date" v-model="form.est_contract_date" class="fi" :class="{ 'is-invalid': form.errors.est_contract_date }" />
                            <v-icon icon="mdi-calendar-blank-outline" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.est_contract_date" class="error-msg">{{ form.errors.est_contract_date }}</div>
                    </div>
                    <div v-if="fields.est_start_date.is_visible" class="form-group">
                        <label class="form-label">{{ fields.est_start_date.label }}</label>
                        <div class="sw-date">
                            <input type="date" v-model="form.est_start_date" class="fi" :class="{ 'is-invalid': form.errors.est_start_date }" />
                            <v-icon icon="mdi-calendar-blank-outline" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.est_start_date" class="error-msg">{{ form.errors.est_start_date }}</div>
                    </div>
                    <div v-if="fields.est_follow_up_date.is_visible" class="form-group">
                        <label class="form-label">{{ fields.est_follow_up_date.label }}</label>
                        <div class="sw-date">
                            <input type="date" v-model="form.est_follow_up_date" class="fi" :class="{ 'is-invalid': form.errors.est_follow_up_date }" />
                            <v-icon icon="mdi-calendar-blank-outline" size="18" color="#2ecc5e" class="sel-icon"/>
                        </div>
                        <div v-if="form.errors.est_follow_up_date" class="error-msg">{{ form.errors.est_follow_up_date }}</div>
                    </div>
                    <div class="form-group row-full mt-2">
                        <label class="form-label">Referral ?</label>
                        <div class="ref-label" @click="form.is_referral = !form.is_referral" style="user-select:none;">
                            <div style="width:16px; height:16px; border-radius:50%; border:1.5px solid #2ecc5e; display:flex; align-items:center; justify-content:center; background:#fff; flex-shrink:0;">
                                <div v-show="form.is_referral" style="width:8px; height:8px; border-radius:50%; background:#2ecc5e;"></div>
                            </div>
                            <span>Is Referral</span>
                        </div>
                        <div v-if="form.errors.is_referral" class="error-msg">{{ form.errors.is_referral }}</div>
                    </div>
                    <div v-if="fields.meeting_note.is_visible" class="form-group row-full mt-2">
                        <label class="form-label">{{ fields.meeting_note.label }}</label>
                        <textarea v-model="form.meeting_note" class="form-input" rows="3" :class="{ 'is-invalid': form.errors.meeting_note }"></textarea>
                        <div v-if="form.errors.meeting_note" class="error-msg">{{ form.errors.meeting_note }}</div>
                    </div>
                    <div v-if="fields.next_step.is_visible" class="form-group row-full">
                        <label class="form-label">{{ fields.next_step.label }}</label>
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
import { ref, computed } from 'vue';
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

const defaultFormFields = {
    business_name: { label: 'Business Name', is_visible: true },
    contact_name: { label: 'First Name', is_visible: true },
    last_name: { label: 'Last Name', is_visible: true },
    contact_email: { label: 'Contact Email', is_visible: true },
    phone: { label: 'Phone Number', is_visible: true },
    secondary_contact_number: { label: 'Secondary Contact Number', is_visible: true },
    biz_type: { label: 'Business Type', is_visible: true },
    source: { label: 'Lead Source', is_visible: true },
    division: { label: 'Division', is_visible: true },
    township: { label: 'Township', is_visible: true },
    address: { label: 'Address', is_visible: true },
    product: { label: 'Product', is_visible: true },
    package: { label: 'Package', is_visible: true },
    package_total: { label: 'Package Total', is_visible: true },
    discount: { label: 'Discount', is_visible: true },
    note: { label: 'Note', is_visible: true },
    status: { label: 'Status', is_visible: true },
    channel: { label: 'Channel', is_visible: true },
    installation_appointment: { label: 'Installation Appointment', is_visible: true },
    est_contract_date: { label: 'Est. Contract Date', is_visible: true },
    est_start_date: { label: 'Est. Start Date', is_visible: true },
    est_follow_up_date: { label: 'Est. Follow Up Date', is_visible: true },
    is_referral: { label: 'Referral ?', is_visible: true },
    meeting_note: { label: 'Meeting Note', is_visible: true },
    next_step: { label: 'Next Step', is_visible: true },
};



const fields = computed(() => {
    const custom = page.props.auth?.user?.tenant_settings?.form_fields || {};
    let merged = {};
    for (let key in defaultFormFields) {
        merged[key] = {
            label: custom[key]?.label || defaultFormFields[key].label,
            is_visible: custom[key]?.is_visible ?? defaultFormFields[key].is_visible
        };
    }
    return merged;
});
</script>
