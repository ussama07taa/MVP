<template>
  <!-- This template is hidden by default and only visible during printing -->
  <div v-if="order" id="print-area" class="hidden print:block bg-white text-slate-900 font-sans p-0 m-0 w-full overflow-hidden">
    <!-- Header: Compacted -->
    <div class="px-10 py-8 flex justify-between items-start border-b-[4px] border-slate-900 bg-slate-50/30">
      <div class="flex gap-4 items-center">
        <div v-if="settings.company_logo" class="w-16 h-16 bg-white rounded-2xl p-2 shadow-sm border border-slate-100 overflow-hidden flex items-center justify-center">
          <img :src="'/storage/' + settings.company_logo" class="max-w-full max-h-full object-contain">
        </div>
        <div>
          <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase leading-none mb-1">
            {{ companyName }}
          </h1>
          <div class="space-y-0.5 text-slate-500 font-bold text-[9px] uppercase tracking-wider">
            <p v-if="companyPhone">{{ companyPhone }}</p>
            <p v-if="settings.company_email">{{ settings.company_email }}</p>
            <p v-if="settings.company_address" class="truncate max-w-[250px]">{{ settings.company_address }}</p>
          </div>
        </div>
      </div>
      
      <div class="text-right">
        <div class="inline-block px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest mb-2"
             :class="(total - amountPaid) <= 0.01 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
          {{ (total - amountPaid) <= 0.01 ? 'RÉGLÉ ✓' : 'PAIEMENT EN ATTENTE' }}
        </div>
        <h2 class="text-3xl font-black uppercase text-slate-200 tracking-tighter leading-none mb-1">FACTURE</h2>
        <p class="text-sm font-black text-slate-900">{{ currentRef }}</p>
        <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ formattedDate }}</p>
      </div>
    </div>

    <!-- Client & Meta Info: Combined and Compacted -->
    <div class="px-10 py-6 grid grid-cols-2 gap-8 border-b border-slate-100">
      <div>
        <h3 class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-2">CLIENT / DESTINATAIRE</h3>
        <div class="bg-white p-4 rounded-2xl border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,0.05)]">
           <p class="text-lg font-black text-slate-900 mb-0.5">{{ clientName }}</p>
           <p v-if="order.client?.phone" class="text-xs font-bold text-slate-500">{{ order.client.phone }}</p>
        </div>
      </div>
      <div class="flex flex-col justify-center">
        <div class="space-y-3">
          <div class="flex justify-between border-b border-slate-50 pb-1">
            <span class="text-[9px] font-black text-slate-400 uppercase">Réf.</span>
            <span class="text-xs font-black text-slate-900">{{ currentRef }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[9px] font-black text-slate-400 uppercase">Émis le</span>
            <span class="text-xs font-black text-slate-900">{{ formattedDateOnly }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Items Table: Compacted -->
    <div class="px-10 py-0">
      <table class="w-full border-collapse">
        <thead>
          <tr>
            <th class="text-left py-4 px-2 text-[9px] font-black uppercase text-slate-400 border-b border-slate-200 tracking-widest w-10">#</th>
            <th class="text-left py-4 px-2 text-[9px] font-black uppercase text-slate-400 border-b border-slate-200 tracking-widest">Désignation</th>
            <th class="text-center py-4 px-2 text-[9px] font-black uppercase text-slate-400 border-b border-slate-200 tracking-widest w-20">Qté</th>
            <th class="text-right py-4 px-2 text-[9px] font-black uppercase text-slate-400 border-b border-slate-200 tracking-widest w-32 whitespace-nowrap">P. Unitaire</th>
            <th class="text-right py-4 px-2 text-[9px] font-black uppercase text-slate-400 border-b border-slate-200 tracking-widest w-32 whitespace-nowrap">Total TTC</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="(item, index) in groupedItems" :key="index" class="break-inside-avoid">
            <td class="py-3 px-2 text-[10px] font-bold text-slate-300">0{{ index + 1 }}</td>
            <td class="py-3 px-2">
              <p class="text-[11px] font-black text-slate-800 leading-tight">{{ formatItemName(item.name || item.label) }}</p>
              <p v-if="item.description" class="text-[8px] text-slate-400 font-bold mt-0.5 uppercase tracking-wider">{{ item.description }}</p>
            </td>
            <td class="py-3 px-2 text-center">
              <span class="text-[10px] font-black text-slate-900 leading-none">x{{ item.quantity }}</span>
            </td>
            <td class="py-3 px-2 text-right text-[10px] font-bold text-slate-500">{{ Number(item.unit_price || item.unit_sell_price).toFixed(2) }}</td>
            <td class="py-3 px-2 text-right text-[11px] font-black text-slate-900">{{ Number(item.total_price || item.total_line_sell).toFixed(2) }} DH</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Totals Section: Redesigned for Vertical Space Saving -->
    <div class="px-10 py-6 flex justify-between gap-8 items-start break-inside-avoid">
      <div class="flex-1">
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 mt-2">
          <h4 class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">MODALITÉS</h4>
          <p class="text-[9px] font-bold text-slate-500 leading-relaxed">
            Paiement par <span class="text-slate-900">Espèces</span>. <br>
            Toute facture impayée pourra donner lieu à des pénalités.
          </p>
        </div>
      </div>

      <div class="w-72">
        <div class="bg-slate-900 rounded-[1.5rem] p-5 text-white shadow-xl relative overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
          
          <div class="space-y-2 relative z-10">
            <div class="flex justify-between items-center text-white/60">
              <span class="text-[8px] font-black uppercase tracking-widest">SOUS-TOTAL HT</span>
              <span class="text-xs font-bold">{{ (total / 1).toFixed(2) }} DH</span>
            </div>
            
            <div class="pt-3 border-t border-white/10 flex justify-between items-baseline">
              <span class="text-[8px] font-black uppercase tracking-widest text-white/40">TOTAL À PAYER</span>
              <div class="text-right">
                <span class="text-2xl font-black tracking-tighter">{{ total.toFixed(2) }}</span>
                <span class="text-[10px] font-black ml-1">DH</span>
              </div>
            </div>

            <div class="pt-3 border-t border-white/10 space-y-1.5">
              <div class="flex justify-between items-center">
                <span class="text-[8px] font-black uppercase text-white/40">AVANCE</span>
                <span class="text-[11px] font-bold text-emerald-400">- {{ Number(amountPaid).toFixed(2) }} DH</span>
              </div>
              <div class="flex justify-between items-center bg-white/5 px-2 py-1.5 rounded-lg">
                <span class="text-[8px] font-black uppercase text-amber-400">RESTE DÛ</span>
                <span class="text-xs font-black text-amber-500">{{ Math.max(0, total - amountPaid).toFixed(2) }} DH</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer: Tighter -->
    <div class="px-10 py-4 text-center border-t border-slate-50 flex-shrink-0">
      <div v-if="settings.company_ice || settings.company_rc" class="mb-3 flex justify-center gap-6 text-[8px] font-black text-slate-300 uppercase tracking-widest">
        <span v-if="settings.company_ice">ICE: {{ settings.company_ice }}</span>
        <span v-if="settings.company_rc">RC: {{ settings.company_rc }}</span>
      </div>
      <p class="text-[9px] font-bold text-slate-400 leading-relaxed uppercase tracking-wider italic">
        {{ settings.invoice_footer_text || 'Merci pour votre confiance !' }}
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
const settings = computed(() => page.props.settings || {});
const companyName = computed(() => settings.value.company_name || tenant.value?.name || 'Mon Entreprise');
const companyPhone = computed(() => settings.value.company_phone || '');

const currentRef = computed(() => {
  if (props.order.invoice_number) return props.order.invoice_number;
  if (props.order.display_reference) return props.order.display_reference;
  return 'FAC-' + (props.order.id || 'N/A');
});

const formatItemName = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client')
    .replace(/Fourniture:\s*/gi, '')
    .replace(/Collage Chant:\s*/gi, '');
};

const groupedItems = computed(() => {
  if (!props.items) return [];
  
  const groups = {};
  
  props.items.forEach(item => {
    // Determine the "base" name by stripping prefixes
    const rawName = item.name || item.label || '';
    const baseName = rawName
      .replace(/Fourniture:\s*/gi, '')
      .replace(/Collage Chant:\s*/gi, '')
      .trim();
    
    // We group by baseName AND quantity to ensure we don't accidentally group different orders of the same product
    // though usually in an invoice they are distinct.
    const key = `${baseName}_${item.quantity}`;
    
    if (!groups[key]) {
      groups[key] = {
        ...item,
        name: baseName, // Use the clean name
        total_price: Number(item.total_price || item.total_line_sell || (item.quantity * (item.unit_price || item.unit_sell_price))),
        is_grouped: false
      };
    } else {
      groups[key].total_price += Number(item.total_price || item.total_line_sell || (item.quantity * (item.unit_price || item.unit_sell_price)));
      groups[key].is_grouped = true;
    }
  });
  
  return Object.values(groups).map(item => {
    // Recalculate unit price for grouped items
    if (item.is_grouped) {
      item.unit_price = item.total_price / item.quantity;
    }
    return item;
  });
});

const formattedDate = computed(() => {
  const date = props.order.created_at || props.order.issue_date || new Date();
  return new Intl.DateTimeFormat('fr-FR', { 
    day: '2-digit', month: 'long', year: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
  }).format(new Date(date));
});

const formattedDateOnly = computed(() => {
  const date = props.order.issue_date || props.order.created_at || new Date();
  return new Intl.DateTimeFormat('fr-FR', { 
    day: '2-digit', month: 'long', year: 'numeric'
  }).format(new Date(date));
});
</script>

<style scoped>
@media print {
  @page {
    size: A4;
    margin: 0 !important;
  }
  body {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  #print-area {
    width: 210mm;
    height: 297mm;
    position: absolute;
    top: 0;
    left: 0;
    overflow: hidden;
  }
}
</style>


