<div class="space-y-6 animate-fade-in">
    <div class="grid grid-cols-2 gap-3">
        <x-dashboard.stat-card label="Total Users" :value="$totalUsers" icon="users" />
        <x-dashboard.stat-card label="Total Products" :value="$totalProducts" icon="package" />
    </div>

    <x-dashboard.panel title="Incoming Messages" icon="message-circle" action="View All Chats">
        <x-dashboard.empty-state message="No new messages" />
    </x-dashboard.panel>

    <x-dashboard.panel title="Upgrade Requests" icon="circle-arrow-up" icon-class="text-purple-500">
        <x-dashboard.empty-state message="No pending upgrade requests" />
    </x-dashboard.panel>

    <x-dashboard.panel title="Pending Transactions" action="View All">
        <x-dashboard.empty-state message="No pending transactions" />
    </x-dashboard.panel>
</div>
