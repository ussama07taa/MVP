<template>
  <div v-if="order" class="hidden print:block bg-white p-10 text-slate-900 max-w-[800px] mx-auto font-sans">
    <div class="flex justify-between items-start border-b-2 border-slate-200 pb-5 mb-8">
      <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase">
          {{ companyName }}
        </h1>
        <p class="text-[11px] text-slate-500 mt-1 font-black uppercase tracking-widest">
          {{ companySubtitle }}
        </p>
      </div>
      <div class="text-right">
        <h2 class="text-3xl font-black uppercase text-slate-200 tracking-widest leading-none">Facture</h2>
        <p class="text-sm font-black text-slate-900 mt-1">N° FAC-{{ order.order_id || order.id }}</p>
        <p class="text-[12px] font-bold text-slate-500 mt-1">{{ formattedDate }}</p>
      </div>
    </div>

    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 mb-8">
      <p class="text-[11px] font-black uppercase text-slate-400 tracking-widest mb-1">Facturé à</p>
      <p class="text-xl font-black text-slate-900">{{ clientName }}</p>
    </div>

    <table class="w-full border-collapse mb-8">
      <thead>
        <tr>
          <th class="text-left py-3 px-4 text-[11px] font-black uppercase text-slate-400 border-b-2 border-slate-200 tracking-widest">Désignation</th>
          <th class="text-right py-3 px-4 text-[11px] font-black uppercase text-slate-400 border-b-2 border-slate-200 tracking-widest">Qté</th>
          <th class="text-right py-3 px-4 text-[11px] font-black uppercase text-slate-400 border-b-2 border-slate-200 tracking-widest">P.U</th>
          <th class="text-right py-3 px-4 text-[11px] font-black uppercase text-slate-400 border-b-2 border-slate-200 tracking-widest">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in items" :key="index" class="border-b border-slate-100">
          <td class="py-4 px-4 text-sm font-bold text-slate-700">{{ formatItemName(item.name) }}</td>
          <td class="py-4 px-4 text-right text-sm font-bold text-slate-700">{{ item.quantity }}</td>
          <td class="py-4 px-4 text-right text-sm font-bold text-slate-700">{{ Number(item.unit_price).toFixed(2) }} DH</td>
          <td class="py-4 px-4 text-right text-sm font-black text-slate-900">{{ (item.quantity * item.unit_price).toFixed(2) }} DH</td>
        </tr>
      </tbody>
    </table>

    <div class="w-80 ml-auto bg-slate-50 rounded-2xl p-6 border border-slate-200">
      <div class="flex justify-between text-sm font-bold text-slate-500 mb-3">
        <span>Sous-total</span>
        <span>{{ total.toFixed(2) }} DH</span>
      </div>
      <div class="flex justify-between text-xl font-black text-slate-900 pt-4 border-t-2 border-dashed border-slate-300 mt-4">
        <span>Total TTC</span>
        <span>{{ total.toFixed(2) }} DH</span>
      </div>
      <div class="flex justify-between text-sm font-black text-emerald-600 mt-4 pt-4 border-t border-slate-200">
        <span>Avance payée</span>
        <span>- {{ Number(amountPaid).toFixed(2) }} DH</span>
      </div>
      <div class="flex justify-between text-lg font-black text-amber-500 mt-3 pt-3 border-t-2 border-slate-200">
        <span class="text-slate-500 text-sm">Reste à payer</span>
        <span>{{ (total - amountPaid).toFixed(2) }} DH</span>
      </div>
    </div>

    <div class="mt-16 text-center border-t border-slate-100 pt-8">
      <p class="text-xs font-bold text-slate-400 leading-relaxed uppercase tracking-wider">
        Merci pour votre confiance ! <br>
        {{ companyName }}{{ companyPhone ? ' - Contact: ' + companyPhone : '' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  order: Object,
  items: Array,
  total: Number,
  amountPaid: Number,
  clientName: String
});

const page = usePage();
const tenant = computed(() => page.props.tenant);
const settings = computed(() => window.appSettings || page.props.settings || {});
const companyName = computed(() => settings.value.company_name || tenant.value?.name || 'Mon Entreprise');
const companyPhone = computed(() => settings.value.company_phone || '');
const companySubtitle = computed(() => settings.value.company_address ? settings.value.company_address.split('\n')[0] : '');

const formatItemName = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client');
};

const formattedDate = computed(() => {
  return new Intl.DateTimeFormat('fr-FR', { 
    day: '2-digit', month: 'short', year: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
  }).format(new Date());
});
</script>
