<template>
    <div class="p-6 max-w-4xl mx-auto">
        <div class="mb-8">
            <h2 class="text-2xl font-black text-slate-800 uppercase">Paramètres de l'entreprise</h2>
            <p class="text-sm text-slate-500">Ces informations apparaîtront sur vos factures et bons de livraison.</p>
        </div>

        <div class="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Company Info -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nom de l'Atelier / Société *</label>
                        <input type="text" v-model="form.company_name" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Téléphone</label>
                            <input type="text" v-model="form.company_phone" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email</label>
                            <input type="email" v-model="form.company_email" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Adresse Complète</label>
                        <textarea v-model="form.company_address" rows="2" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <!-- Legal & Logo -->
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">I.C.E</label>
                            <input type="text" v-model="form.company_ice" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">R.C</label>
                            <input type="text" v-model="form.company_rc" class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 font-medium">
                        </div>
                    </div>
                    
                    <!-- Logo Upload -->
                    <div class="pt-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Logo de la Facture</label>
                        <div class="flex items-center space-x-4">
                            <div class="w-24 h-24 border-2 border-dashed border-slate-300 rounded-xl flex items-center justify-center bg-slate-50 overflow-hidden">
                                <img v-if="previewLogo" :src="previewLogo" class="w-full h-full object-contain" />
                                <i v-else class="fa-solid fa-image text-slate-300 text-2xl"></i>
                            </div>
                            <div>
                                <input type="file" id="logo_upload" class="hidden" @change="handleLogoUpload" accept="image/png, image/jpeg">
                                <button type="button" @click="triggerLogoUpload" class="px-4 py-2 bg-slate-800 text-white text-sm font-bold rounded-xl hover:bg-slate-700 transition-colors shadow-sm">
                                    Changer le Logo
                                </button>
                                <p class="text-[10px] text-slate-400 mt-2">Format: JPG, PNG. Max: 2MB.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer text & Save -->
            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-end">
                <div class="w-full md:w-1/2 mb-4 md:mb-0">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Texte bas de facture (Optionnel)</label>
                    <input type="text" v-model="form.invoice_footer_text" placeholder="Ex: Merci de votre confiance. Les marchandises ne sont ni reprises ni échangées." class="w-full p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
                <button @click="saveSettings" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl transition-all shadow-md shadow-indigo-600/20">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
const toast = useToast();

const form = ref({
    company_name: '',
    company_phone: '',
    company_email: '',
    company_address: '',
    company_ice: '',
    company_rc: '',
    invoice_footer_text: '',
});
const previewLogo = ref(null);
const logoFile = ref(null);
const saving = ref(false);

const loadSettings = async () => {
    try {
        const { data } = await axios.get('/api/admin/settings');
        if (data) {
            form.value = {
                company_name: data.company_name ?? '',
                company_phone: data.company_phone ?? '',
                company_email: data.company_email ?? '',
                company_address: data.company_address ?? '',
                company_ice: data.company_ice ?? '',
                company_rc: data.company_rc ?? '',
                invoice_footer_text: data.invoice_footer_text ?? '',
            };
            if (data.company_logo) {
                previewLogo.value = `/storage/${data.company_logo}`;
            }
        }
    } catch (e) {
        console.error('Impossible de charger les paramètres.', e);
    }
};

const triggerLogoUpload = () => document.getElementById('logo_upload').click();
const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        logoFile.value = file;
        previewLogo.value = URL.createObjectURL(file);
    }
};

const saveSettings = async () => {
    if (saving.value) return;
    saving.value = true;
    try {
        const fd = new FormData();
        Object.entries(form.value).forEach(([k, v]) => {
            if (v !== null && v !== undefined) fd.append(k, v);
        });
        if (logoFile.value) fd.append('logo', logoFile.value);

        await axios.post('/api/admin/settings', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        // Reload to pick up new logo path (and update the previewLogo URL to the
        // server-stored path so future refreshes show it).
        await loadSettings();
        logoFile.value = null;
        toast.success('Paramètres enregistrés avec succès.');
        router.reload();
    } catch (e) {
        const msg = e?.response?.data?.message
            || (e?.response?.data?.errors ? Object.values(e.response.data.errors).flat().join('\n') : 'Erreur de sauvegarde.');
        toast.error(msg);
    } finally {
        saving.value = false;
    }
};

onMounted(loadSettings);
</script>
