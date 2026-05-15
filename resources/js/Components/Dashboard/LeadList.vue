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
                <a href="/sample_leads.csv" download class="btn-outline-green" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <v-icon icon="mdi-download" size="15" />
                    Download CSV
                </a>
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
                        <td>{{ lead.business_name }}</td>
                        <td>{{ lead.first_name }}</td>
                        <td>{{ lead.last_name }}</td>
                        <td>{{ lead.contact_email }}</td>
                        <td>{{ lead.phone }}</td>
                        <td>{{ lead.secondary_contact_number }}</td>
                        <td>{{ lead.biz_type }}</td>
                        <td>{{ lead.source }}</td>
                        <td>{{ lead.division }}</td>
                        <td>{{ lead.township }}</td>
                        <td>{{ lead.address }}</td>
                        <td>{{ lead.product }}</td>
                        <td>{{ lead.package }}</td>
                        <td>{{ lead.package_total ? Number(lead.package_total).toLocaleString() : '' }}</td>
                        <td>{{ lead.discount ? Number(lead.discount).toLocaleString() : '' }}</td>
                        <td>{{ lead.note }}</td>
                        <td>{{ lead.status }}</td>
                        <td>{{ lead.channel }}</td>
                        <td>{{ lead.installation_appointment }}</td>
                        <td>{{ lead.est_contract_date }}</td>
                        <td>{{ lead.est_start_date }}</td>
                        <td>{{ lead.est_follow_up_date }}</td>
                        <td>{{ lead.is_referral ? 'Yes' : 'No' }}</td>
                        <td>{{ lead.meeting_note }}</td>
                        <td>{{ lead.next_step }}</td>
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
