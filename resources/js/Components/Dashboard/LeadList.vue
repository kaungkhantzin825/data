<template>
    <div class="content-card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Lead Management – Outside Leads</h2>
                <p class="card-sub">Manage leads generated outside the system</p>
            </div>
            <div class="card-actions">
                <button class="btn-outline-green" @click="$emit('upload')">
                    <v-icon icon="mdi-upload" size="15" />
                    Upload Lead
                </button>
                <button class="btn-outline-green" @click="$emit('download')">
                    <v-icon icon="mdi-download" size="15" />
                    Download CSV
                </button>
            </div>
        </div>

        <div class="filters-row">
            <div class="filter-group">
                <label class="filter-label">Search</label>
                <input v-model="filters.search" type="text" placeholder="Search..." class="filter-input" />
            </div>
            <div class="filter-group">
                <label class="filter-label">Plan</label>
                <div class="select-wrap">
                    <select v-model="filters.plan" class="filter-select">
                        <option value="">Select...</option>
                        <option v-for="p in availablePlans" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
            </div>
            <div class="filter-group">
                <label class="filter-label">Business Type</label>
                <div class="select-wrap">
                    <select v-model="filters.biz_type" class="filter-select">
                        <option value="">Select...</option>
                        <option v-for="b in availableBizTypes" :key="b" :value="b">{{ b }}</option>
                    </select>
                </div>
            </div>
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <div class="select-wrap">
                    <select v-model="filters.status" class="filter-select">
                        <option value="">Select...</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>
            <div class="filter-btns">
                <button class="btn-search" @click="$emit('apply')">Search</button>
                <button class="btn-reset"  @click="$emit('reset')">Reset</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Business Name</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Secondary Phone</th>
                        <th>Biz Type</th>
                        <th>Source</th>
                        <th>Division</th>
                        <th>Township</th>
                        <th>Address</th>
                        <th>Product</th>
                        <th>Package</th>
                        <th>Package Total</th>
                        <th>Discount</th>
                        <th>Note</th>
                        <th>Status</th>
                        <th>Channel</th>
                        <th>Installation Appt</th>
                        <th>Est. Contract Date</th>
                        <th>Est. Start Date</th>
                        <th>Est. Follow Up</th>
                        <th>Referral</th>
                        <th>Meeting Note</th>
                        <th>Next Step</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(lead, i) in leads.data" :key="lead.id" :class="{stripe: i % 2 === 1}">
                        <td>{{ (leads.from ?? 0) + i }}</td>
                        <td :style="!lead.business_name || lead.business_name === '-' ? 'color: white;' : ''">{{ lead.business_name || '-' }}</td>
                        <td :style="!lead.first_name || lead.first_name === '-' ? 'color: white;' : ''">{{ lead.first_name || '-' }}</td>
                        <td :style="!lead.last_name || lead.last_name === '-' ? 'color: white;' : ''">{{ lead.last_name || '-' }}</td>
                        <td :style="!lead.contact_email || lead.contact_email === '-' ? 'color: white;' : ''">{{ lead.contact_email || '-' }}</td>
                        <td :style="!lead.phone || lead.phone === '-' ? 'color: white;' : ''">{{ lead.phone || '-' }}</td>
                        <td :style="!lead.secondary_contact_number || lead.secondary_contact_number === '-' ? 'color: white;' : ''">{{ lead.secondary_contact_number || '-' }}</td>
                        <td :style="!lead.biz_type || lead.biz_type === '-' ? 'color: white;' : ''">{{ lead.biz_type || '-' }}</td>
                        <td :style="!lead.source || lead.source === '-' ? 'color: white;' : ''">{{ lead.source || '-' }}</td>
                        <td :style="!lead.division || lead.division === '-' ? 'color: white;' : ''">{{ lead.division || '-' }}</td>
                        <td :style="!lead.township || lead.township === '-' ? 'color: white;' : ''">{{ lead.township || '-' }}</td>
                        <td :style="!lead.address || lead.address === '-' ? 'color: white;' : ''">{{ lead.address || '-' }}</td>
                        <td :style="!lead.product || lead.product === '-' ? 'color: white;' : ''">{{ lead.product || '-' }}</td>
                        <td :style="!lead.package || lead.package === '-' ? 'color: white;' : ''">{{ lead.package || '-' }}</td>
                        <td :style="!lead.package_total ? 'color: white;' : ''">{{ lead.package_total ? Number(lead.package_total).toLocaleString() : '-' }}</td>
                        <td :style="!lead.discount ? 'color: white;' : ''">{{ lead.discount ? Number(lead.discount).toLocaleString() : '-' }}</td>
                        <td :style="!lead.note || lead.note === '-' ? 'color: white;' : ''">{{ lead.note || '-' }}</td>
                        <td :style="!lead.status || lead.status === '-' ? 'color: white;' : ''">{{ lead.status || '-' }}</td>
                        <td :style="!lead.channel || lead.channel === '-' ? 'color: white;' : ''">{{ lead.channel || '-' }}</td>
                        <td :style="!lead.installation_appointment || lead.installation_appointment === '-' ? 'color: white;' : ''">{{ lead.installation_appointment || '-' }}</td>
                        <td :style="!lead.est_contract_date || lead.est_contract_date === '-' ? 'color: white;' : ''">{{ lead.est_contract_date || '-' }}</td>
                        <td :style="!lead.est_start_date || lead.est_start_date === '-' ? 'color: white;' : ''">{{ lead.est_start_date || '-' }}</td>
                        <td :style="!lead.est_follow_up_date || lead.est_follow_up_date === '-' ? 'color: white;' : ''">{{ lead.est_follow_up_date || '-' }}</td>
                        <td>{{ lead.is_referral ? 'Yes' : 'No' }}</td>
                        <td :style="!lead.meeting_note || lead.meeting_note === '-' ? 'color: white;' : ''">{{ lead.meeting_note || '-' }}</td>
                        <td :style="!lead.next_step || lead.next_step === '-' ? 'color: white;' : ''">{{ lead.next_step || '-' }}</td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn-edit" @click="$emit('view', lead)" style="background: #3b82f6;">
                                    <v-icon icon="mdi-eye" size="12" />
                                    Detail
                                </button>
                                <button class="btn-edit" @click="$emit('edit', lead)">
                                    <v-icon icon="mdi-pencil" size="12" />
                                    Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!leads.data?.length">
                        <td colspan="27" class="empty-row">No records found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <span class="pag-info">
                Showing {{ leads.from ?? 0 }} to {{ leads.to ?? 0 }} of {{ leads.total ?? 0 }} entries
            </span>
            <div class="pag-btns">
                <button class="pag-btn" :disabled="!leads.prev_page_url" @click="$emit('page', leads.current_page - 1)">‹</button>
                <button v-for="p in pagPages" :key="p" class="pag-btn" :class="{active: p === leads.current_page}" @click="$emit('page', p)">{{ p }}</button>
                <button class="pag-btn" :disabled="!leads.next_page_url" @click="$emit('page', leads.current_page + 1)">›</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    leads: { type: Object, required: true },
    filters: { type: Object, required: true },
    availablePlans: { type: Array, default: () => [] },
    availableBizTypes: { type: Array, default: () => [] }
});

const emit = defineEmits(['upload', 'download', 'apply', 'reset', 'edit', 'view', 'page']);

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const can = (p) => {
    return auth.value?.permissions?.includes(p);
};

const pagPages = computed(() => {
    let current = props.leads.current_page;
    let last = props.leads.last_page;
    let delta = 2;
    let left = current - delta;
    let right = current + delta + 1;
    let range = [];
    let rangeWithDots = [];
    let l;
    for (let i = 1; i <= last; i++) {
        if (i == 1 || i == last || i >= left && i < right) range.push(i);
    }
    for (let i of range) {
        if (l) {
            if (i - l === 2) rangeWithDots.push(l + 1);
            else if (i - l !== 1) rangeWithDots.push('...');
        }
        rangeWithDots.push(i);
        l = i;
    }
    return rangeWithDots;
});
</script>
