<template>
  <div>
    <h1>Admin Dashboard</h1>
    <div v-if="stats">
      <p>Total Users: {{ stats.total_users }}</p>
      <p>Total Targets: {{ stats.total_targets }}</p>
      <p>Total Groups: {{ stats.total_groups }}</p>
      <p>Active Users: {{ stats.active_users }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  stats: Object
})
</script>

