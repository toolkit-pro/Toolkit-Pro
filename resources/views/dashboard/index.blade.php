@extends('layouts.app')

@section('title', 'ড্যাশবোর্ড')

@section('content')
<div class="container-custom py-8">
    <!-- Welcome Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            স্বাগতম, {{ auth()->user()->name }}! 👋
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            আপনার Toolkit Pro ড্যাশবোর্ডে স্বাগতম। আজকের সারাংশ দেখুন।
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Tools Used -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">মোট টুলস ব্যবহার</p>
                    <p class="text-3xl font-bold text-primary-600">{{ $stats['total_tools_used'] ?? 0 }}</p>
                </div>
                <div class="bg-primary-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Points Balance -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">পয়েন্ট ব্যালেন্স</p>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($points ?? 0) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Badges Earned -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ব্যাজ অর্জিত</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $badges_count ?? 0 }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Automation Count -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">সক্রিয় অটোমেশন</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $automations_count ?? 0 }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tools & Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Tools -->
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold">সাম্প্রতিক টুলস</h2>
            </div>
            <div class="card-body">
                @if(isset($recentTools) && $recentTools->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($recentTools as $tool)
                            <li class="py-3 flex items-center justify-between">
                                <a href="{{ route('tools.show', $tool->slug) }}" class="flex items-center hover:text-primary-600">
                                    @if($tool->icon)
                                        <img src="{{ $tool->icon_url }}" alt="{{ $tool->name }}" class="w-10 h-10 rounded-lg mr-3">
                                    @endif
                                    <div>
                                        <p class="font-medium">{{ $tool->name }}</p>
                                        <p class="text-sm text-gray-500">{{ format_date($tool->created_at) }}</p>
                                    </div>
                                </a>
                                <span class="badge badge-primary">{{ $tool->category->name ?? 'General' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500 text-center py-8">কোনো সাম্প্রতিক টুলস নেই</p>
                @endif
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold">সাম্প্রতিক কার্যক্রম</h2>
            </div>
            <div class="card-body">
                @if(isset($activities) && $activities->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($activities as $activity)
                            <li class="py-3 flex items-start">
                                <div class="bg-gray-100 rounded-full p-2 mr-3">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm">{{ $activity->description }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500 text-center py-8">কোনো কার্যক্রম নেই</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-8">
        <div class="card-header">
            <h2 class="text-lg font-semibold">দ্রুত অ্যাকশন</h2>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('tools.index') }}" class="btn btn-primary">
                    🔍 টুলস খুঁজুন
                </a>
                <a href="{{ route('my-tools.create') }}" class="btn btn-outline">
                    ➕ নতুন টুল তৈরি
                </a>
                <a href="{{ route('automations.index') }}" class="btn btn-secondary">
                    ⚡ অটোমেশন তৈরি
                </a>
                <a href="{{ route('dashboard.reports') }}" class="btn btn-outline">
                    📊 রিপোর্ট দেখুন
                </a>
            </div>
        </div>
    </div>

    <!-- Achievements Progress -->
    @if(isset($achievements) && count($achievements) > 0)
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold">অর্জন অগ্রগতি</h2>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    @foreach($achievements as $achievement)
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium">{{ $achievement['name'] }}</span>
                                <span class="text-sm text-gray-500">{{ $achievement['current'] }}/{{ $achievement['target'] }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" 
                                     style="width: {{ $achievement['percentage'] }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // রিয়েল-টাইম নোটিফিকেশন
    Echo.private('user.{{ auth()->id() }}')
        .notification((notification) => {
            toastr.success(notification.message);
        });
</script>
@endpush
