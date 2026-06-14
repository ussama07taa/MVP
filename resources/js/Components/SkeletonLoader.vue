<template>
  <div :class="[
    'animate-shimmer rounded-xl',
    type === 'text' ? 'h-4 w-full mb-2' : '',
    type === 'title' ? 'h-8 w-3/4 mb-4' : '',
    type === 'avatar' ? 'h-12 w-12 rounded-full mb-0' : '',
    type === 'card' ? 'h-32 w-full mb-0' : '',
    type === 'button' ? 'h-10 w-24 mb-0' : ''
  ]"></div>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'text',
    validator: (val) => ['text', 'title', 'avatar', 'card', 'button', 'list-item'].includes(val)
  }
});
</script>
