@extends('layouts.app')
<script>
    window.dashboardData = @json($dashboardData);
</script>
@section('content')
  <div class="px-4 py-6 md:px-6 2xl:px-10">
    <!-- Header -->
    <x-ecommerce.header :userName="$dashboardData['userName']" />

    <!-- Summary Metrics -->
    <x-ecommerce.ecommerce-metrics :accounts="$dashboardData['accounts']" />

    <!-- Statistics Chart -->
    <div class="mt-6">
      <x-ecommerce.statistics-chart 
        :sales="$dashboardData['monthlySales']" 
        :profit="$dashboardData['monthlyProfit']" 
        :startDate="$dashboardData['startDate']" 
        :endDate="$dashboardData['endDate']" 
      />
    </div>

    <!-- Bottom Charts Split -->
    <div class="grid grid-cols-12 gap-6 mt-6">
      <div class="col-span-12 xl:col-span-5">
        <x-ecommerce.customer-demographic :topProducts="$dashboardData['topProducts']" />
      </div>
      <div class="col-span-12 xl:col-span-7">
        <x-ecommerce.monthly-target 
          :incomeTrend="$dashboardData['incomeTrend']" 
          :expenseTrend="$dashboardData['expenseTrend']" 
        />
      </div>
    </div>

    <!-- Recent Products -->
    <div class="mt-6">
      <x-ecommerce.recent-orders :products="$dashboardData['recentProducts']" />
    </div>
  </div>

  <div class="mt-12 text-center text-xs text-blue-500/60 dark:text-gray-500">
    » Somos parte de <span class="font-bold">Xpandecorp</span>
  </div>
@endsection
