<template>
  <div>
    <h1>All Targets</h1>
    <div v-if="targets && targets.data">
      <div v-for="target in targets.data" :key="target.id">
        <p>{{ target.domain_or_url }} - {{ target.current_status }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  targets: Object
})
</script>

