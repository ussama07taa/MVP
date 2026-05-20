<template>
  <div class="max-w-7xl mx-auto pb-10">
    <header class="mb-8 flex justify-between items-end">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center">
          <UsersIcon class="w-6 h-6 mr-3 text-brand-600"/> Fournisseurs &amp; Dettes
        </h1>
        <p class="text-slate-500 text-sm mt-1">Gérez vos fournisseurs, consultez vos crédits (ce que vous devez) et enregistrez vos paiements.</p>
      </div>

      <!-- Actualiser Button (Master UX) -->
      <button @click="loadSuppliers" 
        :class="isLoading ? 'opacity-50 pointer-events-none' : ''"
        class="group relative p-3.5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:shadow-md hover:border-brand-300 transition-all duration-300 active:scale-90"
        title="Actualiser">
        <RotateCwIcon :class="isLoading ? 'animate-spin' : 'group-hover:rotate-180'" class="w-5 h-5 text-brand-600 transition-transform duration-500" />
      </button>
    </header>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center transition-all hover:shadow-md">
        <div class="p-4 rounded-2xl bg-blue-50 text-blue-600 mr-4">
          <TruckIcon class="w-8 h-8"/>
        </div>
        <div>
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Fournisseurs</p>
          <p class="text-2xl font-black text-slate-800 tracking-tight">{{ suppliers.length }}</p>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border-l-4 border-red-500 p-6 flex items-center transition-all hover:shadow-md">
        <div class="p-4 rounded-2xl bg-red-50 text-red-600 mr-4">
          <TrendingDownIcon class="w-8 h-8"/>
        </div>
        <div>
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Dettes (À Payer)</p>
          <p class="text-2xl font-black text-red-600 tracking-tight">{{ totalDebtGlobal.toFixed(2) }} DH</p>
        </div>
      </div>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
        <h2 class="font-black text-slate-700 uppercase text-xs tracking-widest">Liste des Fournisseurs</h2>
        <div class="relative max-w-xs">
          <SearchIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="searchQuery" type="text" placeholder="Rechercher..." class="pl-10 pr-4 py-2 border-slate-200 rounded-xl text-xs w-full focus:ring-brand-500">
        </div>
      </div>
      <div class="overflow-x-auto -mx-4 sm:mx-0 rounded-xl">
        <table class="min-w-full divide-y divide-slate-100">
          <thead class="bg-slate-50/50">
            <tr>
              <th scope="col" class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Nom / Raison Sociale</th>
              <th scope="col" class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Téléphone</th>
              <th scope="col" class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Crédit (Dette)</th>
              <th scope="col" class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-50">
            <tr v-for="sup in filteredSuppliers" :key="sup.id" class="hover:bg-slate-50 transition-colors group">
              <td class="px-8 py-5 whitespace-nowrap font-black text-slate-900 text-sm">{{ sup.name }}</td>
              <td class="px-8 py-5 whitespace-nowrap text-xs font-bold text-slate-500 tracking-tight">{{ sup.phone || '-' }}</td>
              <td class="px-8 py-5 whitespace-nowrap">
                <span :class="sup.total_debt > 0 ? 'text-red-600 font-black bg-red-50 px-4 py-1.5 rounded-xl border border-red-100 text-xs' : 'text-emerald-600 font-black bg-emerald-50 px-4 py-1.5 rounded-xl border border-emerald-100 text-xs'">
                  {{ parseFloat(sup.total_debt).toFixed(2) }} DH
                </span>
              </td>
              <td class="px-8 py-5 whitespace-nowrap text-right">
                <div class="flex justify-end gap-2">
                  <button @click="openPaymentModal(sup)" :disabled="sup.total_debt <= 0" 
                          class="text-white bg-brand-600 hover:bg-brand-500 disabled:opacity-30 disabled:cursor-not-allowed px-5 py-2 rounded-xl transition-all font-black text-[10px] uppercase tracking-widest shadow-lg shadow-brand-900/10 active:scale-95">
                    Régler Dette
                  </button>
                  <button @click="viewHistory(sup)" class="text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-xl transition-all font-black text-[10px] uppercase tracking-widest active:scale-95">
                    <HistoryIcon class="w-4 h-4"/>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredSuppliers.length === 0">
              <td colspan="4" class="px-8 py-16 text-center text-slate-300 font-black uppercase tracking-widest text-sm">Aucun fournisseur trouvé</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PAYMENT MODAL -->
    <TransitionRoot as="template" :show="isPaymentModalOpen">
      <Dialog as="div" class="relative z-50" @close="isPaymentModalOpen = false">
        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" />
        </TransitionChild>
        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="relative transform overflow-hidden rounded-[32px] bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100">
                <div class="bg-white px-8 pt-8 pb-6">
                  <div class="flex items-center mb-6">
                     <div class="p-3 bg-brand-50 text-brand-600 rounded-2xl mr-4">
                        <WalletIcon class="w-6 h-6"/>
                     </div>
                     <div>
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">
                            {{ selectedInvoice ? 'Règlement Facture' : 'Règlement Fournisseur' }}
                        </h3>
                        <p class="text-slate-500 font-bold text-sm">
                            {{ selectedInvoice ? (selectedInvoice.reference_invoice || 'SANS RÉF') : selectedSupplier?.name }}
                        </p>
                     </div>
                  </div>

                  <div class="bg-rose-50 border border-rose-100 rounded-2xl p-5 mb-8 text-center">
                    <span class="block text-[10px] font-black text-rose-400 uppercase tracking-widest mb-1">Reste à payer</span>
                    <span class="text-2xl font-black text-rose-600 tracking-tight">
                        {{ selectedInvoice ? parseFloat(selectedInvoice.total_amount - selectedInvoice.amount_paid).toFixed(2) : (selectedSupplier ? parseFloat(selectedSupplier.total_debt).toFixed(2) : 0) }} DH
                    </span>
                  </div>

                  <div class="space-y-6">
                    <div>
                      <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Montant versé (DH)</label>
                      <input type="number" v-model="paymentForm.amount" placeholder="0.00" class="block w-full rounded-2xl border-slate-200 bg-slate-50 p-4 focus:ring-2 focus:ring-brand-500 font-black text-xl text-slate-800">
                    </div>
                    <div>
                      <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Méthode</label>
                      <select v-model="paymentForm.payment_method" class="block w-full rounded-2xl border-slate-200 bg-slate-50 p-4 font-bold text-slate-700">
                        <option value="cash">💵 Espèces</option>
                        <option value="check">🏦 Chèque</option>
                        <option value="transfer">📱 Virement / Versement</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="bg-slate-50 px-8 py-6 sm:flex sm:flex-row-reverse gap-3">
                  <button @click="submitPayment" :disabled="!paymentForm.amount || paymentForm.amount <= 0 || (selectedInvoice ? paymentForm.amount > (selectedInvoice.total_amount - selectedInvoice.amount_paid) : (selectedSupplier && paymentForm.amount > selectedSupplier.total_debt))" 
                          class="inline-flex w-full justify-center rounded-2xl bg-brand-600 px-8 py-4 text-sm font-black text-white shadow-xl shadow-brand-900/10 hover:bg-brand-500 sm:w-auto uppercase tracking-widest transition-all active:scale-95 disabled:opacity-30">Confirmer</button>
                  <button @click="isPaymentModalOpen = false" class="mt-3 inline-flex w-full justify-center rounded-2xl bg-white px-8 py-4 text-sm font-bold text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-100 sm:mt-0 sm:w-auto transition-all">Annuler</button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- CLEAN SUPPLIER ANALYTICS MODAL -->
    <div v-if="isHistoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white rounded-[2rem] w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]" @click.stop>
            
            <!-- MODAL HEADER -->
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">
                        Analyse Fournisseur: <span class="text-brand-600">{{ selectedSupplier?.name }}</span>
                    </h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Récapitulatif des volumes, factures et règlements</p>
                </div>
                <button @click="isHistoryModalOpen = false" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 rounded-full transition-all">
                    <XIcon class="w-6 h-6" />
                </button>
            </div>

            <!-- MODAL BODY (Scrollable) -->
            <div class="p-8 overflow-y-auto custom-scrollbar">
                
                <!-- TOP METRICS: 4 CLEAN CARDS -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Dette Actuelle (What you owe) -->
                    <div class="bg-red-50/50 border border-red-100 p-5 rounded-2xl relative overflow-hidden">
                        <TrendingDownIcon class="absolute -right-2 -bottom-2 w-16 h-16 text-red-500/10" />
                        <span class="block text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">Dette Actuelle</span>
                        <span class="text-2xl font-black text-red-600">{{ selectedSupplier ? parseFloat(selectedSupplier.total_debt).toFixed(2) : 0 }} DH</span>
                    </div>
                    
                    <!-- Total Déboursé (What you paid) -->
                    <div class="bg-emerald-50/50 border border-emerald-100 p-5 rounded-2xl relative overflow-hidden">
                        <TrendingUpIcon class="absolute -right-2 -bottom-2 w-16 h-16 text-emerald-500/10" />
                        <span class="block text-[10px] font-bold text-emerald-500 uppercase tracking-wider mb-1">Total Déboursé</span>
                        <span class="text-2xl font-black text-emerald-700">{{ historySummary.total_paid_global ? historySummary.total_paid_global.toFixed(2) : 0 }} DH</span>
                    </div>

                    <!-- Volume MDF -->
                    <div class="bg-blue-50/50 border border-blue-100 p-5 rounded-2xl">
                        <span class="block text-[10px] font-bold text-blue-400 uppercase tracking-wider mb-1">Panneaux Achetés</span>
                        <div class="flex items-end space-x-2">
                            <span class="text-2xl font-black text-blue-600">{{ historySummary.total_panels || 0 }}</span>
                            <span class="text-sm font-bold text-blue-400 mb-1">Pcs</span>
                        </div>
                    </div>

                    <!-- Volume Bandchant -->
                    <div class="bg-amber-50/50 border border-amber-100 p-5 rounded-2xl">
                        <span class="block text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-1">Bandchants</span>
                        <div class="flex items-end space-x-2">
                            <span class="text-2xl font-black text-amber-600">{{ historySummary.total_cantos || 0 }}</span>
                            <span class="text-sm font-bold text-amber-500 mb-1">Mètres</span>
                        </div>
                    </div>
                </div>

                <!-- TWO COLUMNS: INVOICES vs PAYMENTS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- LEFT: Factures d'Achat (Dettes) -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-lg bg-red-100 text-red-500 flex items-center justify-center">
                                    <FileTextIcon class="w-4 h-4" />
                                </div>
                                <h3 class="text-sm font-black text-slate-700 uppercase tracking-wider">Factures d'Achat</h3>
                            </div>
                            <div class="relative w-48">
                                <SearchIcon class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input v-model="searchInvoiceQuery" type="text" placeholder="Chercher facture..." class="pl-8 pr-3 py-1.5 border-slate-200 rounded-lg text-[10px] w-full focus:ring-brand-500">
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <!-- Loop Invoices -->
                            <div v-for="invoice in filteredPurchaseHistory" :key="invoice.id" 
                                 class="border border-slate-100 rounded-xl overflow-hidden hover:border-slate-300 transition-colors bg-white">
                                <div @click="toggleInvoice(invoice.id)" class="flex items-center justify-between p-4 hover:bg-slate-50 transition-colors group cursor-pointer select-none">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center group-hover:text-brand-500 transition-colors">
                                            <FileTextIcon class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-800 uppercase">{{ invoice.reference_invoice || 'SANS RÉF' }}</p>
                                            <p class="text-[10px] text-slate-400 font-medium">{{ new Date(invoice.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <p class="text-sm font-black text-slate-700">{{ invoice.net_amount || invoice.total_price || invoice.amount_paid }} DH</p>
                                            <p v-if="invoice.total_amount - invoice.amount_paid > 0" class="text-[10px] font-bold text-rose-500">Reste: {{ parseFloat(invoice.total_amount - invoice.amount_paid).toFixed(2) }} DH</p>
                                            <p v-else class="text-[10px] font-bold text-emerald-500">Réglée</p>
                                        </div>
                                        <button v-if="invoice.total_amount - invoice.amount_paid > 0" @click.stop="openSpecificPaymentModal(invoice)" class="bg-brand-50 border border-brand-200 text-brand-600 hover:bg-brand-600 hover:text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase transition-colors shadow-sm active:scale-95">Payer</button>
                                        <ChevronDownIcon :class="expandedInvoices.includes(invoice.id) ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform duration-300 ml-2" />
                                    </div>
                                </div>
                                
                                <!-- EXPANDED DETAILS -->
                                <div v-show="expandedInvoices.includes(invoice.id)" class="bg-slate-50 border-t border-slate-100 p-4">
                                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Détails des articles</h4>
                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200">
                                            <thead class="bg-slate-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-[9px] font-black text-slate-500 uppercase">Article</th>
                                                    <th class="px-3 py-2 text-center text-[9px] font-black text-slate-500 uppercase">Qté</th>
                                                    <th class="px-3 py-2 text-right text-[9px] font-black text-slate-500 uppercase">Prix U.</th>
                                                    <th class="px-3 py-2 text-right text-[9px] font-black text-slate-500 uppercase">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-slate-50">
                                                    <td class="px-3 py-2 text-[10px] font-bold text-slate-700 max-w-[120px] truncate" :title="item.item_name">{{ item.item_name }}</td>
                                                    <td class="px-3 py-2 text-[10px] font-medium text-slate-600 text-center">{{ item.quantity }}</td>
                                                    <td class="px-3 py-2 text-[10px] font-medium text-slate-600 text-right">{{ parseFloat(item.unit_price).toFixed(2) }} DH</td>
                                                    <td class="px-3 py-2 text-[10px] font-black text-slate-800 text-right">{{ parseFloat(item.total_price || item.net_total_price || (item.quantity * item.unit_price)).toFixed(2) }} DH</td>
                                                </tr>
                                                <tr v-if="!invoice.items || invoice.items.length === 0">
                                                    <td colspan="4" class="px-3 py-4 text-center text-[10px] text-slate-400 font-bold">Aucun article enregistré.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!filteredPurchaseHistory || filteredPurchaseHistory.length === 0" class="p-6 text-center border border-dashed border-slate-200 rounded-xl">
                                <p class="text-xs text-slate-400 font-bold">Aucune facture trouvée.</p>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Flux de Paiements (Règlements) -->
                    <div>
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-500 flex items-center justify-center">
                                <WalletIcon class="w-4 h-4" />
                            </div>
                            <h3 class="text-sm font-black text-slate-700 uppercase tracking-wider">Flux de Paiements</h3>
                        </div>

                        <div class="space-y-3">
                            <!-- Loop Payments -->
                            <div v-for="payment in allPayments" :key="payment.id" 
                                 class="relative p-4 rounded-xl border border-emerald-100 bg-emerald-50/30 hover:bg-emerald-50 transition-colors">
                                <!-- Timeline Line -->
                                <div class="absolute left-0 top-4 bottom-4 w-1 bg-emerald-400 rounded-r-full"></div>
                                
                                <div class="flex justify-between items-start pl-2">
                                    <div>
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[9px] font-black rounded uppercase">
                                                Reçu
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-400">{{ new Date(payment.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase mt-2">
                                            Mode: <span class="text-slate-800">{{ payment.payment_method }}</span> 
                                            <span v-if="payment.purchase" class="text-brand-500">• Fac: {{ payment.purchase.reference_invoice }}</span>
                                            <span v-else-if="payment.purchase_id" class="text-brand-500">• Fac #{{ payment.purchase_id }}</span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-black text-emerald-600">+ {{ payment.amount }} DH</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State for Payments -->
                            <div v-if="!allPayments || allPayments.length === 0" class="p-6 text-center border border-dashed border-slate-200 rounded-xl">
                                <p class="text-xs text-slate-400 font-bold">Aucun paiement enregistré.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-end items-center">
                <button @click="isHistoryModalOpen = false" class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 bg-white border border-slate-200 hover:border-slate-300 rounded-xl transition-colors shadow-sm">
                    Fermer
                </button>
            </div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { 
  UsersIcon, TruckIcon, TrendingDownIcon, SearchIcon, HistoryIcon, WalletIcon, 
  FileTextIcon, ChevronDownIcon, LayersIcon, PaletteIcon, CheckCircleIcon, TrendingUpIcon,
  RotateCwIcon, XIcon
} from 'lucide-vue-next';
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';

const suppliers = ref([]);
const searchQuery = ref('');
const searchInvoiceQuery = ref('');
const isPaymentModalOpen = ref(false);
const isHistoryModalOpen = ref(false);
const isLoading = ref(false);
const selectedSupplier = ref(null);
const selectedInvoice = ref(null);
const paymentForm = ref({ amount: null, payment_method: 'cash', purchase_id: null });
const purchaseHistory = ref([]);
const allPayments = ref([]);
const expandedInvoices = ref([]);
const historySummary = ref({ total_panels: 0, total_cantos: 0, total_paid_global: 0 });

const loadSuppliers = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/admin/suppliers');
    suppliers.value = res.data;
  } catch (error) {
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

const filteredSuppliers = computed(() => {
  if (!searchQuery.value) return suppliers.value;
  const q = searchQuery.value.toLowerCase();
  return suppliers.value.filter(s => s.name.toLowerCase().includes(q));
});

const filteredPurchaseHistory = computed(() => {
  if (!searchInvoiceQuery.value) return purchaseHistory.value;
  const q = searchInvoiceQuery.value.toLowerCase();
  return purchaseHistory.value.filter(inv => {
    const refMatch = inv.reference_invoice && inv.reference_invoice.toLowerCase().includes(q);
    const itemMatch = inv.items && inv.items.some(item => item.item_name && item.item_name.toLowerCase().includes(q));
    return refMatch || itemMatch;
  });
});

const totalDebtGlobal = computed(() => {
  return suppliers.value.reduce((sum, sup) => sum + parseFloat(sup.total_debt || 0), 0);
});

const openPaymentModal = (supplier) => {
  selectedSupplier.value = supplier;
  selectedInvoice.value = null;
  paymentForm.value = { amount: null, payment_method: 'cash', purchase_id: null };
  isPaymentModalOpen.value = true;
};

const openSpecificPaymentModal = (invoice) => {
  const reste = invoice.total_amount - invoice.amount_paid;
  if (reste <= 0) return;
  
  selectedInvoice.value = invoice;
  paymentForm.value = { 
     amount: reste, 
     payment_method: 'cash',
     purchase_id: invoice.id 
  };
  isPaymentModalOpen.value = true;
};

const refreshHistory = async () => {
  if (!selectedSupplier.value) return;
  try {
    const res = await axios.get(`/api/admin/suppliers/${selectedSupplier.value.id}/history`);
    purchaseHistory.value = res.data.purchases;
    allPayments.value = res.data.all_payments || [];
    historySummary.value = res.data.summary;
    // Update selected supplier debt from the fresh data
    const updatedSup = suppliers.value.find(s => s.id === selectedSupplier.value.id);
    if (updatedSup) selectedSupplier.value = updatedSup;
  } catch (e) {
    console.error("Refresh History Error:", e);
  }
};

const viewHistory = async (supplier) => {
  selectedSupplier.value = supplier;
  expandedInvoices.value = []; 
  try {
    await refreshHistory();
    isHistoryModalOpen.value = true;
    if(purchaseHistory.value.length > 0) {
        expandedInvoices.value.push(purchaseHistory.value[0].id);
    }
  } catch (e) {
    alert('Erreur');
  }
};

const toggleInvoice = (id) => {
  const index = expandedInvoices.value.indexOf(id);
  if (index > -1) {
    expandedInvoices.value.splice(index, 1);
  } else {
    expandedInvoices.value.push(id);
  }
};

const getItemColor = (cat) => {
  if (cat === 'mdf') return 'bg-blue-500';
  if (cat === 'canto') return 'bg-amber-500';
  return 'bg-emerald-500';
};

const submitPayment = async () => {
  try {
    await axios.post(`/api/admin/suppliers/${selectedSupplier.value.id}/pay`, paymentForm.value);
    isPaymentModalOpen.value = false;
    await loadSuppliers();
    // Live refresh history if open
    if (isHistoryModalOpen.value) {
        await refreshHistory();
    }
    alert('Paiement enregistré avec succès !');
  } catch (error) {
    alert(error.response?.data?.error || 'Erreur');
  }
};

onMounted(() => loadSuppliers());
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
