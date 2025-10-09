<template>
  <div>
    <h1>Dashboard</h1>
    <div v-if="stats">
      <p>Total Targets: {{ stats.total_targets }}</p>
      <p>Blocked: {{ stats.blocked_targets }}</p>
      <p>Accessible: {{ stats.ok_targets }}</p>
    </div>
    <div v-if="recentChecks">
      <h2>Recent Checks</h2>
      <div v-for="check in recentChecks" :key="check.id">
        <p>{{ check.target?.domain_or_url }} - {{ check.status }}</p>
      </div>
    </div>
    <div v-if="groups">
      <h2>Groups</h2>
      <div v-for="group in groups" :key="group.id">
        <p>{{ group.name }} ({{ group.targets_count }} targets)</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  stats: Object,
  recentChecks: Array,
  groups: Array
})
</script>

