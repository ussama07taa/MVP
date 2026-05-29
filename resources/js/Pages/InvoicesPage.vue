<template>
  <div class="min-h-screen font-sans selection:bg-brand-500 selection:text-white">

    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Factures & Devis</h1>
        <p class="text-sm font-bold text-slate-400 mt-1">Gestion complète de la facturation</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="openCreateModal('quote')" class="group bg-gradient-to-r from-amber-500 to-orange-500 text-white px-5 py-3 rounded-2xl font-black shadow-lg shadow-amber-500/25 hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center text-sm">
          <FileTextIcon class="w-5 h-5 mr-2 group-hover:rotate-12 transition-transform duration-300" /> Nouveau Devis
        </button>
        <button @click="openCreateModal('invoice')" class="group bg-gradient-to-r from-brand-600 to-indigo-600 text-white px-5 py-3 rounded-2xl font-black shadow-lg shadow-brand-500/25 hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center text-sm">
          <PlusIcon class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" /> Nouvelle Facture
        </button>
      </div>
    </header>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-4 flex items-center gap-4">
        <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center border border-amber-100">
          <ClockIcon class="w-5 h-5 text-amber-600" />
        </div>
        <div>
          <p class="text-2xl font-black text-slate-900">{{ summary.pending_quotes }}</p>
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Devis en attente</p>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-4 flex items-center gap-4">
        <div class="w-11 h-11 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100">
          <AlertTriangleIcon class="w-5 h-5 text-rose-500" />
        </div>
        <div>
          <p class="text-2xl font-black text-slate-900">{{ summary.expired_quotes }}</p>
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Devis expirés</p>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-4 flex items-center gap-4">
        <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center border border-blue-100">
          <BanknoteIcon class="w-5 h-5 text-blue-600" />
        </div>
        <div>
          <p class="text-2xl font-black text-slate-900">{{ summary.unpaid_invoices }}</p>
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Factures impayées</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6">
      <button v-for="f in filters" :key="f.value" @click="activeFilter = f.value"
        :class="activeFilter === f.value ? 'bg-brand-600 text-white shadow-lg shadow-brand-200' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
        class="px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all">
        {{ f.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4 animate-pulse">
      <div v-for="i in 5" :key="i" class="bg-white h-20 rounded-2xl border border-slate-100"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredInvoices.length === 0" class="bg-white rounded-3xl border border-slate-200/50 shadow-sm p-16 text-center">
      <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <FileTextIcon class="w-10 h-10 text-slate-300" />
      </div>
      <p class="text-xl font-black text-slate-700">Aucun document trouvé</p>
      <p class="text-sm text-slate-400 mt-2">Créez votre première facture ou devis.</p>
    </div>

    <!-- Invoice List -->
    <div v-else class="space-y-3">
      <div v-for="inv in filteredInvoices" :key="inv.id"
        class="bg-white rounded-2xl border border-slate-200/60 shadow-[0_2px_10px_rgb(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:border-brand-200 transition-all duration-300 p-5 group cursor-pointer"
        @click="openEditModal(inv)">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm"
              :class="inv.type === 'invoice' ? 'bg-brand-50 border border-brand-100' : 'bg-amber-50 border border-amber-100'">
              <FileTextIcon class="w-5 h-5" :class="inv.type === 'invoice' ? 'text-brand-600' : 'text-amber-600'" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-black text-slate-900 text-sm">{{ inv.invoice_number }}</span>
                <span :class="statusClasses(inv.status)" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider border">{{ statusLabel(inv.status) }}</span>
                <span v-if="inv.type === 'invoice' && inv.validated_at" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider border bg-emerald-50 text-emerald-700 border-emerald-200">Validée</span>
              </div>
              <p class="text-xs font-bold text-slate-400 mt-0.5">
                {{ inv.client?.name || 'Client inconnu' }} • {{ formatDate(inv.issue_date) }}
                <span v-if="inv.type === 'quote' && inv.expiry_date" class="ml-2" :class="inv.is_expired ? 'text-rose-500' : 'text-slate-400'">
                  (expire {{ formatDate(inv.expiry_date) }})
                </span>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-6">
            <div class="text-right">
              <span class="text-lg font-black text-slate-900">{{ (inv.total || 0).toFixed(2) }}</span>
              <span class="text-xs font-bold text-slate-400 ml-1">DH</span>
              <div v-if="inv.remaining > 0" class="text-[10px] font-black text-rose-500">Reste: {{ (inv.remaining || 0).toFixed(2) }} DH</div>
            </div>
            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
              <!-- Validate invoice (not yet validated) -->
              <button v-if="inv.type === 'invoice' && !inv.validated_at && inv.status !== 'cancelled'" @click.stop="validateInvoice(inv)" title="Valider (stock + dette)"
                class="w-8 h-8 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center transition-colors">
                <ShieldCheckIcon class="w-4 h-4" />
              </button>
              <!-- Pay invoice (validated + remaining > 0) -->
              <button v-if="inv.type === 'invoice' && inv.validated_at && inv.remaining > 0" @click.stop="openPayModal(inv)" title="Encaisser"
                class="w-8 h-8 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center transition-colors">
                <BanknoteIcon class="w-4 h-4" />
              </button>
              <!-- Quote-specific actions -->
              <button v-if="inv.type === 'quote' && !['accepted','refused','cancelled','expired'].includes(inv.status)" @click.stop="updateQuoteStatus(inv, 'accepted')" title="Accepter"
                class="w-8 h-8 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center transition-colors">
                <CheckCircleIcon class="w-4 h-4" />
              </button>
              <button v-if="inv.type === 'quote' && !['accepted','refused','cancelled','expired'].includes(inv.status)" @click.stop="updateQuoteStatus(inv, 'refused')" title="Refuser"
                class="w-8 h-8 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-colors">
                <XCircleIcon class="w-4 h-4" />
              </button>
              <button v-if="inv.type === 'quote' && inv.status === 'accepted'" @click.stop="convertQuote(inv)" title="Convertir en Facture"
                class="w-8 h-8 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center transition-colors">
                <ArrowRightCircleIcon class="w-4 h-4" />
              </button>
              <!-- Common actions -->
              <button @click.stop="duplicateInvoice(inv)" title="Dupliquer"
                class="w-8 h-8 bg-indigo-50 hover:bg-indigo-100 text-indigo-500 rounded-lg flex items-center justify-center transition-colors">
                <CopyIcon class="w-4 h-4" />
              </button>
              <button @click.stop="printInvoice(inv)" title="Imprimer"
                class="w-8 h-8 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center transition-colors">
                <PrinterIcon class="w-4 h-4" />
              </button>
              <button @click.stop="shareOnWhatsApp(inv)" title="Envoyer sur WhatsApp (PDF)"
                class="w-8 h-8 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center transition-colors">
                <MessageCircleIcon class="w-4 h-4" />
              </button>
              <button @click.stop="deleteInvoice(inv)" title="Supprimer"
                class="w-8 h-8 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-colors">
                <Trash2Icon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CREATE / EDIT MODAL -->
    <div v-if="showModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-[100] flex items-start justify-center p-4 pt-10 overflow-y-auto">
      <div class="bg-white rounded-[2rem] w-full max-w-4xl shadow-2xl my-8">
        <!-- Modal Header -->
        <div class="p-8 border-b border-slate-100 flex justify-between items-center sticky top-0 bg-white rounded-t-[2rem] z-10">
          <div>
            <h3 class="font-black text-2xl text-slate-900 tracking-tight">{{ editingInvoice ? 'Modifier' : 'Créer' }} un Document</h3>
            <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ form.invoice_number || 'Nouveau' }}</p>
          </div>
          <button @click="showModal = false" class="w-10 h-10 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 hover:text-slate-900 hover:bg-slate-200 transition-all">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-8 space-y-6">
          <!-- Type + Client Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Type *</label>
              <select v-model="form.type" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500">
                <option value="invoice">Facture</option>
                <option value="quote">Devis</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Client *</label>
              <select v-model="form.client_id" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500">
                <option value="" disabled>Sélectionner...</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
          </div>

          <!-- Dates + Tax + Validity Row -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Date d'émission *</label>
              <input type="date" v-model="form.issue_date" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500">
            </div>
            <div class="space-y-2">
              <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Échéance</label>
              <input type="date" v-model="form.due_date" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500">
            </div>
            <div class="space-y-2">
              <label class="text-xs font-black text-slate-500 uppercase tracking-widest">TVA %</label>
              <input type="number" v-model="form.tax_rate" min="0" max="100" step="0.01" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500" placeholder="0">
            </div>
            <div v-if="form.type === 'quote'" class="space-y-2">
              <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Validité (jours)</label>
              <input type="number" v-model="form.validity_days" min="1" max="365" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500" placeholder="15">
            </div>
          </div>

          <!-- Line Items -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Articles *</label>
              <div class="flex gap-2">
                <button @click="showStockPicker = true" class="text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl hover:bg-emerald-100 transition-colors uppercase tracking-wider flex items-center">
                  <PackageIcon class="w-3 h-3 mr-1" /> Depuis Stock
                </button>
                <button @click="addFreeItem" class="text-[10px] font-black text-brand-600 bg-brand-50 px-3 py-1.5 rounded-xl hover:bg-brand-100 transition-colors uppercase tracking-wider flex items-center">
                  <PlusIcon class="w-3 h-3 mr-1" /> Libre
                </button>
              </div>
            </div>

            <!-- Table Header -->
            <div class="hidden md:grid grid-cols-12 gap-2 px-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">
              <div class="col-span-4">Désignation</div>
              <div class="col-span-2">Catégorie</div>
              <div class="col-span-1">Qté</div>
              <div class="col-span-1">Unité</div>
              <div class="col-span-2">Prix U.</div>
              <div class="col-span-1 text-right">Total</div>
              <div class="col-span-1"></div>
            </div>

            <!-- Item Rows -->
            <div v-for="(item, idx) in form.items" :key="idx"
              class="grid grid-cols-12 gap-2 items-center p-3 rounded-xl border transition-colors"
              :class="item.item_type ? 'bg-emerald-50/30 border-emerald-100 hover:border-emerald-200' : 'bg-slate-50/50 border-slate-100 hover:border-brand-200'">
              <div class="col-span-12 md:col-span-4 relative">
                <input v-model="item.description" placeholder="Description..." class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" :readonly="!!item.item_type">
                <span v-if="item.item_type" class="absolute top-0.5 right-1 text-[8px] font-black text-emerald-500 bg-emerald-50 px-1.5 py-0.5 rounded">STOCK</span>
              </div>
              <select v-model="item.category" class="col-span-6 md:col-span-2 p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500" :disabled="!!item.item_type">
                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
              </select>
              <div class="col-span-3 md:col-span-1 relative">
                <input type="number" v-model="item.quantity" min="0.01" step="0.01" class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-center focus:ring-2 focus:ring-brand-500/20">
                <span v-if="item.available != null" class="absolute -bottom-3 left-0 text-[8px] font-bold text-slate-400">max: {{ item.available }}</span>
              </div>
              <input v-model="item.unit" class="col-span-3 md:col-span-1 p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-center focus:ring-2 focus:ring-brand-500/20" placeholder="u" :readonly="!!item.item_type">
              <input type="number" v-model="item.unit_price" min="0" step="0.01" class="col-span-6 md:col-span-2 p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-right focus:ring-2 focus:ring-brand-500/20" placeholder="0.00">
              <div class="col-span-4 md:col-span-1 text-right text-xs font-black text-slate-700">{{ lineTotal(item) }}</div>
              <button @click="removeItem(idx)" class="col-span-2 md:col-span-1 w-8 h-8 mx-auto bg-rose-50 hover:bg-rose-100 text-rose-400 hover:text-rose-600 rounded-lg flex items-center justify-center transition-colors">
                <Trash2Icon class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Totals -->
          <div class="bg-slate-900 rounded-2xl p-6 text-white space-y-3">
            <div class="flex justify-between text-sm"><span class="text-slate-400 font-bold">Sous-total</span><span class="font-black">{{ computedSubtotal.toFixed(2) }} DH</span></div>
            <div class="flex justify-between text-sm"><span class="text-slate-400 font-bold">TVA ({{ form.tax_rate || 0 }}%)</span><span class="font-black">{{ computedTax.toFixed(2) }} DH</span></div>
            <div class="h-px bg-slate-700"></div>
            <div class="flex justify-between text-xl"><span class="font-bold text-slate-300">Total TTC</span><span class="font-black text-emerald-400">{{ computedTotal.toFixed(2) }} DH</span></div>
          </div>

          <!-- Notes -->
          <div class="space-y-2">
            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Notes</label>
            <textarea v-model="form.notes" rows="2" class="w-full p-3.5 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 resize-none" placeholder="Conditions, remarques..."></textarea>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-8 bg-slate-50 flex justify-end gap-3 rounded-b-[2rem] border-t border-slate-100">
          <button @click="showModal = false" class="px-6 py-3 font-black text-slate-500 hover:text-slate-800 uppercase text-xs tracking-widest transition-colors">Annuler</button>
          <button @click="saveInvoice" :disabled="isSaving" class="px-8 py-3 font-black text-white bg-brand-600 hover:bg-brand-700 rounded-2xl shadow-lg shadow-brand-200 disabled:opacity-50 uppercase text-xs tracking-widest flex items-center transition-all">
            <Loader2Icon v-if="isSaving" class="w-4 h-4 mr-2 animate-spin" />
            {{ editingInvoice ? 'Mettre à jour' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- STOCK PICKER MODAL -->
    <div v-if="showStockPicker" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-[200] flex items-start justify-center p-4 pt-10 overflow-y-auto">
      <div class="bg-white rounded-[2rem] w-full max-w-3xl shadow-2xl my-8">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
          <h3 class="font-black text-xl text-slate-900">Sélectionner depuis le Stock</h3>
          <button @click="showStockPicker = false" class="w-10 h-10 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 hover:text-slate-900 hover:bg-slate-200 transition-all">
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Stock tabs -->
        <div class="flex border-b border-slate-100">
          <button v-for="t in stockTabs" :key="t.key" @click="stockTab = t.key"
            :class="stockTab === t.key ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
            class="flex-1 px-4 py-3 text-xs font-black uppercase tracking-widest border-b-2 transition-colors">
            {{ t.label }}
          </button>
        </div>

        <!-- Search -->
        <div class="p-4">
          <input v-model="stockSearch" placeholder="Rechercher..." class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500">
        </div>

        <!-- Stock List -->
        <div class="p-4 max-h-[50vh] overflow-y-auto space-y-2">
          <div v-if="filteredStockItems.length === 0" class="text-center py-8 text-sm text-slate-400 font-bold">Aucun article trouvé</div>
          <div v-for="si in filteredStockItems" :key="`${si.item_type}-${si.item_id}`"
            @click="addStockItem(si)"
            class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-emerald-200 hover:bg-emerald-50/50 cursor-pointer transition-all">
            <div>
              <p class="text-xs font-black text-slate-800">{{ si.description }}</p>
              <p class="text-[10px] font-bold text-slate-400 mt-0.5">
                {{ si.unit_price.toFixed(2) }} DH/{{ si.unit }}
                <span v-if="si.available != null" class="ml-2 text-emerald-600">Stock: {{ si.available }}</span>
              </p>
            </div>
            <PlusIcon class="w-5 h-5 text-emerald-500" />
          </div>
        </div>
      </div>
    </div>

    <!-- PAYMENT MODAL -->
    <div v-if="showPayModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-[100] flex items-center justify-center p-4">
      <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl p-8 space-y-6">
        <h3 class="font-black text-2xl text-slate-900">Encaisser un paiement</h3>
        <p class="text-sm font-bold text-slate-500">{{ payingInvoice?.invoice_number }} — Reste: <span class="text-rose-600">{{ payRemaining.toFixed(2) }} DH</span></p>
        <div class="space-y-2">
          <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Montant *</label>
          <input type="number" v-model="payAmount" :max="payRemaining" min="0.01" step="0.01" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-black text-lg text-slate-900 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500">
        </div>
        <div class="flex gap-2">
          <button @click="payAmount = payRemaining" class="px-4 py-2 text-xs font-black bg-emerald-50 text-emerald-700 rounded-xl hover:bg-emerald-100 transition-colors">Tout payer</button>
        </div>
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <button @click="showPayModal = false" class="px-6 py-3 font-black text-slate-500 uppercase text-xs tracking-widest">Annuler</button>
          <button @click="submitPayment" :disabled="isSaving" class="px-8 py-3 font-black text-white bg-emerald-600 hover:bg-emerald-700 rounded-2xl shadow-lg disabled:opacity-50 uppercase text-xs tracking-widest flex items-center transition-all">
            <Loader2Icon v-if="isSaving" class="w-4 h-4 mr-2 animate-spin" />
            Encaisser
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
const toast = useToast();
const page = usePage();
import { FileTextIcon, PlusIcon, XIcon, Trash2Icon, PrinterIcon, ArrowRightCircleIcon, Loader2Icon, CheckCircleIcon, XCircleIcon, CopyIcon, ClockIcon, AlertTriangleIcon, BanknoteIcon, ShieldCheckIcon, PackageIcon, MessageCircleIcon } from 'lucide-vue-next';

const invoices = ref([]);
const clients = ref([]);
const isLoading = ref(true);
const showModal = ref(false);
const isSaving = ref(false);
const editingInvoice = ref(null);
const activeFilter = ref('all');
const summary = ref({ pending_quotes: 0, expired_quotes: 0, unpaid_invoices: 0 });

// Stock picker
const showStockPicker = ref(false);
const stockItems = ref({ panels: [], cantos: [], services: [] });
const stockTab = ref('panels');
const stockSearch = ref('');
const stockTabs = [
  { key: 'panels', label: 'Panneaux (MDF/LATI)' },
  { key: 'cantos', label: 'Bandchant' },
  { key: 'services', label: 'Services' },
];

// Payment modal
const showPayModal = ref(false);
const payingInvoice = ref(null);
const payAmount = ref(0);
const payRemaining = ref(0);

const filters = [
  { label: 'Tous', value: 'all' },
  { label: 'Factures', value: 'invoice' },
  { label: 'Devis', value: 'quote' },
  { label: 'Brouillons', value: 'draft' },
  { label: 'Acceptés', value: 'accepted' },
  { label: 'Payées', value: 'paid' },
  { label: 'Expirés', value: 'expired' },
];

const categories = [
  { label: 'MDF', value: 'mdf' },
  { label: 'LATI', value: 'lati' },
  { label: 'Bandchant', value: 'canto' },
  { label: 'Quincaillerie', value: 'hardware' },
  { label: 'Main d\'oeuvre', value: 'labor' },
  { label: 'Service', value: 'service' },
  { label: 'Autre', value: 'other' },
];

const defaultItem = () => ({ description: '', category: 'other', quantity: 1, unit: 'unité', unit_price: 0, item_type: null, item_id: null, available: null });

const form = ref({
  type: 'invoice', client_id: '', issue_date: new Date().toISOString().split('T')[0],
  due_date: '', tax_rate: 0, validity_days: 15, notes: '', invoice_number: '', items: [defaultItem()],
});

const filteredInvoices = computed(() => {
  if (activeFilter.value === 'all') return invoices.value;
  if (['invoice', 'quote'].includes(activeFilter.value)) return invoices.value.filter(i => i.type === activeFilter.value);
  return invoices.value.filter(i => i.status === activeFilter.value);
});

const filteredStockItems = computed(() => {
  const items = stockItems.value[stockTab.value] || [];
  if (!stockSearch.value) return items;
  const q = stockSearch.value.toLowerCase();
  return items.filter(i => i.description.toLowerCase().includes(q));
});

const lineTotal = (item) => (Number(item.quantity) * Number(item.unit_price)).toFixed(2);
const computedSubtotal = computed(() => form.value.items.reduce((s, i) => s + Number(i.quantity) * Number(i.unit_price), 0));
const computedTax = computed(() => computedSubtotal.value * (Number(form.value.tax_rate) / 100));
const computedTotal = computed(() => computedSubtotal.value + computedTax.value);

const addFreeItem = () => form.value.items.push(defaultItem());
const removeItem = (idx) => { if (form.value.items.length > 1) form.value.items.splice(idx, 1); };

const addStockItem = (si) => {
  form.value.items.push({
    description: si.description,
    category: si.category,
    quantity: 1,
    unit: si.unit,
    unit_price: si.unit_price,
    item_type: si.item_type,
    item_id: si.item_id,
    available: si.available,
  });
  showStockPicker.value = false;
  stockSearch.value = '';
};

const statusLabel = (s) => ({ draft: 'Brouillon', sent: 'Envoyé', paid: 'Payée', partial: 'Partielle', cancelled: 'Annulée', accepted: 'Accepté', refused: 'Refusé', expired: 'Expiré' }[s] || s);
const statusClasses = (s) => ({
  draft: 'bg-slate-50 text-slate-500 border-slate-200',
  sent: 'bg-blue-50 text-blue-600 border-blue-100',
  paid: 'bg-emerald-50 text-emerald-600 border-emerald-100',
  partial: 'bg-amber-50 text-amber-600 border-amber-100',
  cancelled: 'bg-slate-50 text-slate-400 border-slate-200',
  accepted: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  refused: 'bg-rose-50 text-rose-600 border-rose-100',
  expired: 'bg-red-50 text-red-600 border-red-200',
}[s] || 'bg-slate-50 text-slate-500 border-slate-200');

const formatDate = (d) => d ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d)) : '';

const fetchData = async () => {
  isLoading.value = true;
  try {
    const [resInv, resCl, resSum, resStock] = await Promise.all([
      axios.get('/api/admin/invoices'),
      axios.get('/api/admin/clients'),
      axios.get('/api/admin/invoices-summary'),
      axios.get('/api/admin/invoices/stock-items'),
    ]);
    invoices.value = resInv.data;
    clients.value = resCl.data;
    summary.value = resSum.data;
    stockItems.value = resStock.data;
  } catch (e) { console.error('Fetch error', e); } finally { isLoading.value = false; }
};

const openCreateModal = (type = 'invoice') => {
  editingInvoice.value = null;
  form.value = { type, client_id: '', issue_date: new Date().toISOString().split('T')[0], due_date: '', tax_rate: 0, validity_days: 15, notes: '', invoice_number: '', items: [defaultItem()] };
  showModal.value = true;
};

const openEditModal = (inv) => {
  editingInvoice.value = inv;
  form.value = {
    type: inv.type, client_id: inv.client?.id || '', issue_date: inv.issue_date, due_date: inv.due_date || '',
    tax_rate: inv.tax_rate, validity_days: inv.validity_days || 15, notes: inv.notes || '', invoice_number: inv.invoice_number,
    items: inv.items.map(i => ({ description: i.description, category: i.category, quantity: Number(i.quantity), unit: i.unit, unit_price: Number(i.unit_price), item_type: i.item_type || null, item_id: i.item_id || null, available: null })),
  };
  showModal.value = true;
};

const saveInvoice = async () => {
  if (!form.value.client_id || form.value.items.length === 0) { toast.warning('Veuillez remplir le client et au moins un article.'); return; }
  isSaving.value = true;
  try {
    if (editingInvoice.value) {
      await axios.put(`/api/admin/invoices/${editingInvoice.value.id}`, form.value);
    } else {
      await axios.post('/api/admin/invoices', form.value);
    }
    toast.success(editingInvoice.value ? 'Document mis à jour !' : 'Document créé !');
    showModal.value = false;
    await fetchData();
  } catch (e) {
    toast.error(e.response?.data?.error || 'Erreur lors de la sauvegarde.');
  } finally { isSaving.value = false; }
};

const validateInvoice = async (inv) => {
  if (!confirm(`Valider la facture ${inv.invoice_number} ?\n\nCela va :\n• Déduire le stock des articles liés\n• Ajouter ${inv.total.toFixed(2)} DH comme dette au client`)) return;
  try {
    await axios.post(`/api/admin/invoices/${inv.id}/validate`);
    toast.success('Facture validée ! Stock déduit et dette ajoutée.');
    await fetchData();
  } catch (e) { toast.error(e.response?.data?.error || 'Erreur lors de la validation.'); }
};

const openPayModal = (inv) => {
  payingInvoice.value = inv;
  payRemaining.value = (inv.total || 0) - (inv.amount_paid || 0);
  payAmount.value = payRemaining.value;
  showPayModal.value = true;
};

const submitPayment = async () => {
  if (payAmount.value <= 0) { toast.warning('Montant invalide.'); return; }
  isSaving.value = true;
  try {
    await axios.post(`/api/admin/invoices/${payingInvoice.value.id}/pay`, { amount: payAmount.value });
    toast.success(`Paiement de ${payAmount.value.toFixed(2)} DH enregistré !`);
    showPayModal.value = false;
    await fetchData();
  } catch (e) { toast.error(e.response?.data?.error || 'Erreur.'); }
  finally { isSaving.value = false; }
};

const deleteInvoice = async (inv) => {
  if (!confirm(`Supprimer ${inv.invoice_number} ?`)) return;
  try { await axios.delete(`/api/admin/invoices/${inv.id}`); toast.success('Document supprimé.'); await fetchData(); }
  catch (e) { toast.error(e.response?.data?.error || 'Erreur.'); }
};

const convertQuote = async (inv) => {
  if (!confirm(`Convertir le devis ${inv.invoice_number} en facture ?`)) return;
  try { await axios.post(`/api/admin/invoices/${inv.id}/convert`); toast.success('Devis converti en facture !'); await fetchData(); }
  catch (e) { toast.error(e.response?.data?.error || 'Erreur.'); }
};

const updateQuoteStatus = async (inv, status) => {
  const labels = { accepted: 'accepter', refused: 'refuser' };
  if (!confirm(`Voulez-vous ${labels[status]} le devis ${inv.invoice_number} ?`)) return;
  try {
    await axios.patch(`/api/admin/invoices/${inv.id}/status`, { status });
    toast.success(`Devis ${status === 'accepted' ? 'accepté' : 'refusé'} !`);
    await fetchData();
  } catch (e) { toast.error(e.response?.data?.error || 'Erreur.'); }
};

const duplicateInvoice = async (inv) => {
  try {
    const res = await axios.post(`/api/admin/invoices/${inv.id}/duplicate`);
    toast.success(res.data.message || 'Document dupliqué !');
    await fetchData();
  } catch (e) { toast.error(e.response?.data?.error || 'Erreur lors de la duplication.'); }
};

const printInvoice = (inv) => {
  // Dispatch global print event
  window.dispatchEvent(new CustomEvent('global-print', {
    detail: {
      order: inv,
      items: inv.items || [],
      total: Number(inv.total),
      amountPaid: Number(inv.total) - Number(inv.remaining || 0),
      clientName: inv.client?.name || 'Client'
    }
  }));
};

const shareOnWhatsApp = (inv) => {
  if (!inv.client?.phone) {
    toast.warning("Le client n'a pas de numéro de téléphone.");
    return;
  }

  // 1. Generate PDF URL
  const pdfUrl = `/api/admin/invoices/${inv.id}/pdf`;
  
  // 2. Open PDF in a new tab so user can save/see it
  window.open(pdfUrl, '_blank');

  // 3. Prepare WhatsApp message
  const phone = inv.client.phone.replace(/\D/g, '');
  const text = `Bonjour ${inv.client.name}, voici votre ${inv.type === 'invoice' ? 'facture' : 'devis'} ${inv.invoice_number} d'un montant de ${inv.total.toFixed(2)} DH.`;
  
  // Use web.whatsapp.com for more "Direct" desktop feel, or wa.me for universal
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  const waUrl = isMobile 
    ? `https://wa.me/${phone}?text=${encodeURIComponent(text)}`
    : `https://web.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`;
  
  // 4. Open WhatsApp
  window.open(waUrl, '_blank');
};

const formatItemName = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client');
};

onMounted(() => fetchData());
</script>
