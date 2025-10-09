<template>
  <div>
    <h1>System Statistics</h1>
    <div v-if="statistics">
      <p>Total Users: {{ statistics.total_users }}</p>
      <p>Total Targets: {{ statistics.total_targets }}</p>
      <p>Total Checks: {{ statistics.total_checks }}</p>
      <p>Blocked Targets: {{ statistics.blocked_targets }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  statistics: Object
})
</script>

