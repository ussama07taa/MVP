<template>
  <div class="flex flex-col items-center justify-center p-12 text-center">
    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-inner">
      <component :is="icon" class="w-10 h-10 text-slate-300" />
    </div>
    <h3 class="text-xl font-black text-slate-800 mb-2">{{ title }}</h3>
    <p class="text-slate-500 font-medium max-w-xs mx-auto mb-8">{{ message }}</p>
    <slot name="action">
      <button v-if="actionLabel" @click="$emit('action')" class="btn-primary">
        <PlusIcon v-if="showPlus" class="w-5 h-5 mr-2" />
        {{ actionLabel }}
      </button>
    </slot>
  </div>
</template>

<script setup>
import { PlusIcon } from 'lucide-vue-next';

defineProps({
  title: String,
  message: String,
  icon: { type: Object, default: () => null },
  actionLabel: String,
  showPlus: { type: Boolean, default: true }
});

defineEmits(['action']);
</script>
