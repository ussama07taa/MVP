<template>
  <div class="min-h-screen font-sans selection:bg-brand-500 selection:text-white relative">
    
    <!-- Ambient Background (Master UI) -->
    <div class="fixed top-0 left-0 w-full h-[600px] pointer-events-none z-0 overflow-hidden">
      <div class="absolute -top-32 -left-32 w-[30rem] h-[30rem] bg-brand-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
      <div class="absolute top-0 right-0 w-[30rem] h-[30rem] bg-indigo-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>
    </div>

    <div class="relative z-10 w-full max-w-7xl mx-auto">
      <!-- Header -->
      <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-5">
          <div class="w-16 h-16 bg-white rounded-[1.5rem] flex items-center justify-center shadow-sm border border-slate-100 shrink-0 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-500/10 to-indigo-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <FileTextIcon class="w-8 h-8 text-brand-600 relative z-10 group-hover:scale-110 transition-transform duration-500" />
          </div>
          <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-none mb-1">Factures & Devis</h1>
            <p class="text-slate-500 font-medium text-sm">Gestion complète de la facturation et des encaissements.</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button @click="openCreateModal('quote')" class="btn-warning !px-5 !py-3 shadow-sm hover:shadow-md transition-all rounded-2xl flex items-center gap-2">
            <FileTextIcon class="w-4 h-4" /> Nouveau Devis
          </button>
          <button @click="openCreateModal('invoice')" class="btn-primary !px-5 !py-3 shadow-lg shadow-brand-200 hover:shadow-brand-300 transition-all rounded-2xl flex items-center gap-2">
            <PlusIcon class="w-4 h-4" /> Nouvelle Facture
          </button>
        </div>
      </header>

      <!-- KPI Loading State -->
      <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <SkeletonLoader v-for="i in 3" :key="i" type="card" class="!w-full !h-32 !rounded-[2rem]" />
      </div>

      <!-- Summary Cards -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <!-- Pending -->
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden flex items-center gap-6">
          <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-50 rounded-full blur-2xl group-hover:bg-amber-100 transition-colors opacity-50 pointer-events-none"></div>
          <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center border border-amber-100 shrink-0 relative z-10">
            <ClockIcon class="w-6 h-6 text-amber-600" />
          </div>
          <div class="relative z-10">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Devis en attente</p>
            <p class="text-3xl font-black text-slate-900 tracking-tight">{{ summary.pending_quotes }}</p>
          </div>
        </div>
        <!-- Expired -->
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden flex items-center gap-6">
          <div class="absolute -right-6 -top-6 w-32 h-32 bg-rose-50 rounded-full blur-2xl group-hover:bg-rose-100 transition-colors opacity-50 pointer-events-none"></div>
          <div class="w-14 h-14 bg-rose-50 rounded-2xl flex items-center justify-center border border-rose-100 shrink-0 relative z-10">
            <AlertTriangleIcon class="w-6 h-6 text-rose-500" />
          </div>
          <div class="relative z-10">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Devis Expirés</p>
            <p class="text-3xl font-black text-slate-900 tracking-tight">{{ summary.expired_quotes }}</p>
          </div>
        </div>
        <!-- Unpaid (PRO MAX) -->
        <div class="relative overflow-hidden bg-brand-600 p-6 rounded-[2rem] border border-brand-700 shadow-xl shadow-brand-100 group flex items-center gap-6">
          <div class="absolute inset-0 bg-gradient-to-r from-brand-600 to-indigo-600 opacity-90"></div>
          <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-500 pointer-events-none"></div>
          <div class="w-14 h-14 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/20 shrink-0 relative z-10">
            <BanknoteIcon class="w-6 h-6 text-white" />
          </div>
          <div class="relative z-10">
            <p class="text-[10px] font-black text-brand-200 uppercase tracking-widest mb-1">Factures Impayées</p>
            <p class="text-3xl font-black text-white tracking-tight">{{ summary.unpaid_invoices }}</p>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-6 flex">
        <div class="bg-slate-200/50 backdrop-blur-md p-1.5 rounded-[1.5rem] border border-slate-200/50 flex gap-1 w-full sm:w-auto overflow-x-auto no-scrollbar snap-x">
          <button v-for="f in filters" :key="f.value" @click="activeFilter = f.value"
            :class="activeFilter === f.value ? 'bg-white text-brand-600 shadow-sm border-slate-200/60' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50 border-transparent'"
            class="flex-1 sm:flex-none shrink-0 snap-center px-6 py-3 rounded-[1.25rem] text-[10px] font-black uppercase tracking-widest transition-all border whitespace-nowrap">
            {{ f.label }}
          </button>
        </div>
      </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4">
      <SkeletonLoader v-for="i in 5" :key="i" type="list-item" class="!w-full !h-20" />
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredInvoices.length === 0">
      <EmptyState icon="FileTextIcon" 
                  title="Aucun document trouvé" 
                  message="Créez votre première facture ou devis pour commencer."
                  class="py-16"
                  :showPlus="false" />
    </div>

    <!-- Invoice List -->
      <div v-else class="space-y-4">
        <div v-for="inv in filteredInvoices" :key="inv.id"
          class="bg-white rounded-[2rem] border border-slate-100 shadow-sm transition-all hover:border-brand-200 hover:shadow-md group cursor-pointer overflow-hidden relative"
          @click="openEditModal(inv)">
          
          <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center space-x-5">
              <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm shrink-0 border"
                :class="inv.type === 'invoice' ? 'bg-gradient-to-br from-brand-50 to-indigo-50 border-brand-100' : 'bg-gradient-to-br from-amber-50 to-orange-50 border-amber-100'">
                <FileTextIcon class="w-6 h-6" :class="inv.type === 'invoice' ? 'text-brand-600' : 'text-amber-600'" />
              </div>
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-black text-slate-900 text-base tracking-tight font-heading">{{ inv.invoice_number }}</span>
                  <span :class="statusClasses(inv.status)" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-200/50">{{ statusLabel(inv.status) }}</span>
                  <span v-if="inv.type === 'invoice' && inv.validated_at" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest border border-emerald-200/50 bg-emerald-50 text-emerald-700 shadow-sm">Validée</span>
                </div>
                <p class="text-xs font-bold text-slate-500 flex items-center">
                  <UserIcon class="w-3.5 h-3.5 mr-1 text-slate-400" />
                  {{ inv.client?.name || 'Client inconnu' }}
                  <span class="mx-2 text-slate-300">•</span>
                  <CalendarIcon class="w-3.5 h-3.5 mr-1 text-slate-400" />
                  {{ formatDate(inv.issue_date) }}
                  <span v-if="inv.type === 'quote' && inv.expiry_date" class="ml-2 flex items-center" :class="inv.is_expired ? 'text-rose-500 font-black' : 'text-slate-400'">
                    <ClockIcon class="w-3 h-3 mr-1" /> Expire {{ formatDate(inv.expiry_date) }}
                  </span>
                </p>
              </div>
            </div>
            
            <div class="flex flex-col md:flex-row md:items-center gap-6">
              <div class="text-right">
                <span class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] block mb-0.5">Total TTC</span>
                <span class="text-2xl font-black text-slate-900 tracking-tight">{{ Number(inv.total || 0).toFixed(2) }}</span>
                <span class="text-[10px] font-bold text-slate-400 ml-1">DH</span>
                <div v-if="Number(inv.remaining) > 0" class="text-[10px] font-black text-rose-500 uppercase tracking-widest mt-1 bg-rose-50 w-max ml-auto px-2 py-0.5 rounded-md">Reste: {{ Number(inv.remaining || 0).toFixed(2) }} DH</div>
              </div>
              
              <!-- Quick Actions Slide-in -->
              <div class="flex items-center gap-1.5 md:opacity-0 md:group-hover:opacity-100 transition-all duration-300 transform md:translate-x-4 md:group-hover:translate-x-0">
                <!-- Validate invoice (not yet validated) -->
                <button v-if="inv.type === 'invoice' && !inv.validated_at && inv.status !== 'cancelled'" @click.stop="validateInvoice(inv)" title="Valider (stock + dette)"
                  class="w-10 h-10 bg-emerald-50 hover:bg-emerald-500 text-emerald-600 hover:text-white rounded-[1rem] flex items-center justify-center transition-all shadow-sm hover:shadow-emerald-200">
                  <ShieldCheckIcon class="w-5 h-5" />
                </button>
                <!-- Pay invoice (validated + remaining > 0) -->
                <button v-if="inv.type === 'invoice' && inv.validated_at && Number(inv.remaining) > 0" @click.stop="openPayModal(inv)" title="Encaisser"
                  class="w-10 h-10 bg-emerald-50 hover:bg-emerald-500 text-emerald-600 hover:text-white rounded-[1rem] flex items-center justify-center transition-all shadow-sm hover:shadow-emerald-200">
                  <BanknoteIcon class="w-5 h-5" />
                </button>
                <!-- Quote-specific actions -->
                <button v-if="inv.type === 'quote' && !['accepted','refused','cancelled','expired'].includes(inv.status)" @click.stop="updateQuoteStatus(inv, 'accepted')" title="Accepter"
                  class="w-10 h-10 bg-emerald-50 hover:bg-emerald-500 text-emerald-600 hover:text-white rounded-[1rem] flex items-center justify-center transition-all shadow-sm hover:shadow-emerald-200">
                  <CheckCircleIcon class="w-5 h-5" />
                </button>
                <button v-if="inv.type === 'quote' && !['accepted','refused','cancelled','expired'].includes(inv.status)" @click.stop="updateQuoteStatus(inv, 'refused')" title="Refuser"
                  class="w-10 h-10 bg-rose-50 hover:bg-rose-500 text-rose-500 hover:text-white rounded-[1rem] flex items-center justify-center transition-all shadow-sm hover:shadow-rose-200">
                  <XCircleIcon class="w-5 h-5" />
                </button>
                <button v-if="inv.type === 'quote' && inv.status === 'accepted'" @click.stop="convertQuote(inv)" title="Convertir en Facture"
                  class="w-10 h-10 bg-indigo-50 hover:bg-indigo-500 text-indigo-600 hover:text-white rounded-[1rem] flex items-center justify-center transition-all shadow-sm hover:shadow-indigo-200">
                  <ArrowRightCircleIcon class="w-5 h-5" />
                </button>
                <!-- Common actions -->
                <button v-if="userRole === 'admin'" @click.stop="duplicateInvoice(inv)" title="Dupliquer"
                  class="w-10 h-10 bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 rounded-[1rem] flex items-center justify-center transition-all border border-slate-200 shadow-sm">
                  <CopyIcon class="w-4 h-4" />
                </button>
                <button @click.stop="printInvoice(inv)" title="Imprimer"
                  class="w-10 h-10 bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 rounded-[1rem] flex items-center justify-center transition-all border border-slate-200 shadow-sm">
                  <PrinterIcon class="w-4 h-4" />
                </button>
                <button @click.stop="shareOnWhatsApp(inv)" title="Envoyer sur WhatsApp (PDF)"
                  class="w-10 h-10 bg-emerald-50 border border-emerald-100 hover:bg-emerald-500 text-emerald-600 hover:text-white rounded-[1rem] flex items-center justify-center transition-all shadow-sm hover:shadow-emerald-200">
                  <MessageCircleIcon class="w-4 h-4" />
                </button>
                <button v-if="userRole === 'admin'" @click.stop="deleteInvoice(inv)" title="Supprimer"
                  class="w-10 h-10 bg-rose-50 border border-rose-100 hover:bg-rose-500 text-rose-500 hover:text-white rounded-[1rem] flex items-center justify-center transition-all shadow-sm hover:shadow-rose-200">
                  <Trash2Icon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CREATE / EDIT MODAL (Cinema Glass) -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xl z-[100] flex items-start justify-center p-4 pt-10 overflow-y-auto">
        <div class="bg-white/95 backdrop-blur-3xl rounded-[2.5rem] w-full max-w-4xl shadow-2xl my-8 border border-white/20 transform transition-all duration-300">
          <!-- Modal Header -->
          <div class="p-8 border-b border-white/10 flex justify-between items-center sticky top-0 bg-white/80 backdrop-blur-3xl rounded-t-[2.5rem] z-10 shadow-sm">
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
          <div class="p-8 bg-slate-100/50 backdrop-blur-sm flex justify-end gap-3 rounded-b-[2.5rem] border-t border-slate-100/50">
            <button @click="showModal = false" class="px-8 py-3.5 font-black text-slate-500 hover:text-slate-800 uppercase text-xs tracking-widest transition-colors bg-white border border-slate-200 rounded-2xl hover:shadow-sm">Annuler</button>
            <button @click="saveInvoice" :disabled="isSaving" class="px-8 py-3.5 font-black text-white bg-brand-600 hover:bg-brand-700 rounded-2xl shadow-xl shadow-brand-200 disabled:opacity-50 uppercase text-xs tracking-widest flex items-center transition-all active:scale-95">
              <Loader2Icon v-if="isSaving" class="w-4 h-4 mr-2 animate-spin" />
              {{ editingInvoice ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

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
import { useWhatsApp } from '@/composables/useWhatsApp';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import EmptyState from '@/Components/EmptyState.vue';

const toast = useToast();
const page = usePage();
const authUser = computed(() => page.props.auth.user);
const userRole = computed(() => authUser.value?.role);
import { FileTextIcon, PlusIcon, XIcon, Trash2Icon, PrinterIcon, ArrowRightCircleIcon, Loader2Icon, CheckCircleIcon, XCircleIcon, CopyIcon, ClockIcon, AlertTriangleIcon, BanknoteIcon, ShieldCheckIcon, PackageIcon, MessageCircleIcon, FileSearchIcon, UserIcon, CalendarIcon } from 'lucide-vue-next';

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
  if (!confirm(`Valider la facture ${inv.invoice_number} ?\n\nCela va :\n• Déduire le stock des articles liés\n• Ajouter ${Number(inv.total).toFixed(2)} DH comme dette au client`)) return;
  try {
    await axios.post(`/api/admin/invoices/${inv.id}/validate`);
    toast.success('Facture validée ! Stock déduit et dette ajoutée.');
    await fetchData();
  } catch (e) { toast.error(e.response?.data?.error || 'Erreur lors de la validation.'); }
};

const openPayModal = (inv) => {
  payingInvoice.value = inv;
  payRemaining.value = Number(inv.total || 0) - Number(inv.amount_paid || 0);
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

const shareOnWhatsApp = async (inv) => {
  if (!inv.client?.phone) {
    toast.warning("Le client n'a pas de numéro de téléphone.");
    return;
  }
  try {
    const { data } = await axios.get(`/api/admin/invoices/${inv.id}/share-link`);
    const { shareDocument } = useWhatsApp();
    const result = await shareDocument({
      client: inv.client,
      pdfPath: data.url,
      reference: inv.invoice_number,
      total: inv.total,
      type: inv.type,
    });
    if (result.ok && result.mode === 'file') {
      toast.success('PDF joint — choisissez WhatsApp et le client.');
    } else if (result.ok && result.mode === 'link') {
      toast.success('WhatsApp ouvert avec le lien PDF.');
    } else if (result.error === 'cancelled') {
      return;
    } else if (!result.ok) {
      toast.warning("Le client n'a pas de numéro de téléphone.");
    }
  } catch (e) {
    toast.error('Impossible d\'envoyer sur WhatsApp.');
  }
};

const formatItemName = (name) => {
  if (!name) return '';
  return name
    .replace(/Pose Canto\s*\(?Sel3a\s*(?:d|y|n)?\s*Client\)?/gi, 'Pose de Chant (Fourniture Client)')
    .replace(/Sel3a\s*(?:d|y|n)?\s*Client/gi, 'Fourniture Client');
};

onMounted(() => fetchData());
</script>
