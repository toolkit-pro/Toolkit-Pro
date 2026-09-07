<?php

namespace App\Services;

use App\Models\Tool;
use App\Models\Category;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IntelligentToolEngine
{
    private $aiEngine;
    private $userBehavior;
    private $contextAnalyzer;
    private $mlModels;
    private $cache;
    
    public function __construct()
    {
        $this->cache = Cache::store('redis');
        $this->initializeModels();
    }
    
    /**
     * Initialize ML models.
     */
    private function initializeModels(): void
    {
        $this->mlModels = [
            'intent' => new IntentDetectionModel(),
            'recommendation' => new RecommendationModel(),
            'classification' => new ClassificationModel(),
            'clustering' => new ClusteringModel(),
            'prediction' => new PredictionModel(),
        ];
    }
    
    /**
     * স্মার্ট টুল সাজেশন সিস্টেম
     * মাল্টি-ফ্যাক্টর অ্যানালাইসিস ভিত্তিক
     */
    public function suggestTools($userId, $context = []): Collection
    {
        // ক্যাশে চেক
        $cacheKey = "tool_suggestions_{$userId}_" . md5(json_encode($context));
        if ($cached = $this->cache->get($cacheKey)) {
            return collect($cached);
        }
        
        $suggestions = [
            'contextual' => $this->analyzeContext($context),
            'behavioral' => $this->analyzeBehavior($userId),
            'predictive' => $this->predictNeeds($userId),
            'collaborative' => $this->collaborativeFiltering($userId),
            'trending' => $this->getTrendingTools(),
            'seasonal' => $this->getSeasonalTools(),
            'location_based' => $this->getLocationBasedTools($userId),
            'time_based' => $this->getTimeBasedTools(),
            'device_based' => $this->getDeviceSpecificTools($context),
            'skill_level' => $this->getSkillBasedTools($userId),
            'popular' => $this->getPopularTools(),
            'similar' => $this->getSimilarTools($context),
            'affinity' => $this->getAffinityTools($userId),
        ];
        
        $ranked = $this->rankSuggestions($suggestions);
        
        // ক্যাশে সংরক্ষণ
        $this->cache->put($cacheKey, $ranked->toArray(), 300);
        
        return $ranked;
    }
    
    /**
     * অ্যাডভান্সড কনটেক্সট অ্যানালাইসিস
     */
    private function analyzeContext(array $context): array
    {
        $weights = [
            'time_of_day' => 0.15,
            'user_intent' => 0.30,
            'previous_actions' => 0.25,
            'current_page' => 0.10,
            'search_history' => 0.20,
        ];
        
        $scores = [];
        
        // সময় ভিত্তিক অ্যানালাইসিস
        $hour = now()->hour;
        if ($hour >= 5 && $hour < 12) {
            $scores['time_of_day'] = 0.8; // সকাল
        } elseif ($hour >= 12 && $hour < 17) {
            $scores['time_of_day'] = 0.6; // দুপুর
        } elseif ($hour >= 17 && $hour < 22) {
            $scores['time_of_day'] = 0.9; // সন্ধ্যা
        } else {
            $scores['time_of_day'] = 0.4; // রাত
        }
        
        // ইউজার ইনটেন্ট ডিটেকশন
        if (isset($context['query'])) {
            $intent = $this->detectIntent($context['query']);
            $scores['user_intent'] = $intent['confidence'];
            $scores['intent_type'] = $intent['type'];
        }
        
        // প্রিভিয়াস অ্যাকশন
        if (isset($context['previous_actions'])) {
            $scores['previous_actions'] = count($context['previous_actions']) > 0 ? 0.7 : 0.1;
        }
        
        // কারেন্ট পেজ
        if (isset($context['current_page'])) {
            $pageTypes = [
                'home' => 0.3,
                'search' => 0.9,
                'tool' => 0.7,
                'category' => 0.6,
                'dashboard' => 0.5,
            ];
            $scores['current_page'] = $pageTypes[$context['current_page']] ?? 0.3;
        }
        
        // সার্চ হিস্টোরি
        if (isset($context['search_history'])) {
            $scores['search_history'] = count($context['search_history']) > 0 ? 0.8 : 0.1;
        }
        
        // ওয়েটেড স্কোর
        $weightedScore = 0;
        foreach ($weights as $factor => $weight) {
            if (isset($scores[$factor])) {
                $weightedScore += $scores[$factor] * $weight;
            }
        }
        
        return [
            'score' => $weightedScore,
            'details' => $scores,
            'confidence' => $this->calculateConfidence($weightedScore),
        ];
    }
    
    /**
     * ইউজার বিহেভিয়ার অ্যানালাইসিস
     */
    private function analyzeBehavior($userId): array
    {
        if (!$userId) {
            return ['score' => 0, 'details' => []];
        }
        
        $activities = UserActivity::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        // টুল ব্যবহারের প্যাটার্ন
        $toolUsage = $activities->where('action', 'tool_used')->count();
        $totalActions = $activities->count();
        $usageRate = $totalActions > 0 ? $toolUsage / $totalActions : 0;
        
        // ক্যাটাগরি প্রেফারেন্স
        $categoryPreferences = $this->getCategoryPreferences($activities);
        
        // টাইম স্পেন্ট প্যাটার্ন
        $avgTimeSpent = $activities->avg('duration') ?? 0;
        
        return [
            'score' => min(1, $usageRate),
            'details' => [
                'tool_usage_rate' => $usageRate,
                'category_preferences' => $categoryPreferences,
                'average_time_spent' => $avgTimeSpent,
            ],
        ];
    }
    
    /**
     * প্রেডিক্টিভ নিড অ্যানালাইসিস
     */
    private function predictNeeds($userId): array
    {
        if (!$userId) {
            return ['score' => 0, 'tools' => []];
        }
        
        $recentTools = UserActivity::where('user_id', $userId)
            ->where('action', 'tool_used')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->pluck('tool_id');
        
        $predictedTools = $this->mlModels['prediction']->predictNextTools($recentTools);
        
        return [
            'score' => count($predictedTools) > 0 ? 0.8 : 0.3,
            'tools' => $predictedTools,
        ];
    }
    
    /**
     * কোলাবোরেটিভ ফিল্টারিং
     */
    private function collaborativeFiltering($userId): array
    {
        if (!$userId) {
            return ['score' => 0, 'tools' => []];
        }
        
        // ইউজারের ক্যাটাগরি প্রেফারেন্স
        $userCategories = $this->getUserCategoryIds($userId);
        
        // একই ধরনের ইউজার খুঁজুন
        $similarUsers = User::whereHas('activities', function ($query) use ($userCategories) {
            $query->whereIn('tool_id', function ($subQuery) use ($userCategories) {
                $subQuery->select('tool_id')
                    ->from('tool_categories')
                    ->whereIn('category_id', $userCategories);
            });
        })->where('id', '!=', $userId)->limit(20)->get();
        
        // সিমিলার ইউজারদের টুলস
        $recommendedTools = Tool::whereHas('activities', function ($query) use ($similarUsers) {
            $query->whereIn('user_id', $similarUsers->pluck('id'))
                ->where('action', 'tool_used');
        })->whereNotIn('id', function ($query) use ($userId) {
            $query->select('tool_id')
                ->from('user_activities')
                ->where('user_id', $userId)
                ->where('action', 'tool_used');
        })->limit(20)->get();
        
        return [
            'score' => $recommendedTools->count() > 0 ? 0.7 : 0.2,
            'tools' => $recommendedTools,
        ];
    }
    
    /**
     * ট্রেন্ডিং টুলস
     */
    private function getTrendingTools(): array
    {
        $cacheKey = 'trending_tools_suggestions';
        
        return $this->cache->remember($cacheKey, 1800, function () {
            $tools = Tool::active()
                ->where('uses_count', '>', 1000)
                ->orderBy('uses_count', 'desc')
                ->orderBy('rating', 'desc')
                ->limit(20)
                ->get();
            
            return [
                'score' => $tools->count() > 0 ? 0.6 : 0.1,
                'tools' => $tools,
            ];
        });
    }
    
    /**
     * সিজনাল টুলস
     */
    private function getSeasonalTools(): array
    {
        $month = now()->month;
        $season = $this->getSeason($month);
        
        $cacheKey = "seasonal_tools_{$season}";
        
        return $this->cache->remember($cacheKey, 86400, function () use ($season) {
            $tools = Tool::active()
                ->where('metadata->season', $season)
                ->orWhere('tags', 'like', "%{$season}%")
                ->limit(15)
                ->get();
            
            return [
                'score' => $tools->count() > 0 ? 0.5 : 0.1,
                'tools' => $tools,
            ];
        });
    }
    
    /**
     * লোকেশন ভিত্তিক টুলস
     */
    private function getLocationBasedTools($userId): array
    {
        if (!$userId) {
            return ['score' => 0, 'tools' => []];
        }
        
        $user = User::find($userId);
        if (!$user || !$user->country) {
            return ['score' => 0, 'tools' => []];
        }
        
        $tools = Tool::active()
            ->where('metadata->country', $user->country)
            ->orWhere('languages', 'like', "%{$user->language}%")
            ->limit(15)
            ->get();
        
        return [
            'score' => $tools->count() > 0 ? 0.4 : 0.1,
            'tools' => $tools,
        ];
    }
    
    /**
     * টাইম ভিত্তিক টুলস
     */
    private function getTimeBasedTools(): array
    {
        $hour = now()->hour;
        
        $timeCategories = [
            'morning' => [5, 11],
            'afternoon' => [12, 16],
            'evening' => [17, 21],
            'night' => [22, 4],
        ];
        
        $currentPeriod = 'night';
        foreach ($timeCategories as $period => [$start, $end]) {
            if ($hour >= $start && $hour <= $end) {
                $currentPeriod = $period;
                break;
            }
        }
        
        $tools = Tool::active()
            ->where('metadata->best_time', $currentPeriod)
            ->limit(10)
            ->get();
        
        return [
            'score' => $tools->count() > 0 ? 0.3 : 0.1,
            'tools' => $tools,
        ];
    }
    
    /**
     * ডিভাইস ভিত্তিক টুলস
     */
    private function getDeviceSpecificTools(array $context): array
    {
        $device = $context['device'] ?? 'desktop';
        
        $tools = Tool::active()
            ->where('compatibility', 'like', "%{$device}%")
            ->limit(10)
            ->get();
        
        return [
            'score' => $tools->count() > 0 ? 0.3 : 0.1,
            'tools' => $tools,
        ];
    }
    
    /**
     * স্কিল লেভেল ভিত্তিক টুলস
     */
    private function getSkillBasedTools($userId): array
    {
        if (!$userId) {
            return ['score' => 0, 'tools' => []];
        }
        
        $user = User::find($userId);
        $skillLevel = $user->metadata['skill_level'] ?? 'beginner';
        
        $tools = Tool::active()
            ->where('metadata->skill_level', $skillLevel)
            ->limit(10)
            ->get();
        
        return [
            'score' => $tools->count() > 0 ? 0.4 : 0.1,
            'tools' => $tools,
        ];
    }
    
    /**
     * পপুলার টুলস
     */
    private function getPopularTools(): array
    {
        $cacheKey = 'popular_tools_suggestions';
        
        return $this->cache->remember($cacheKey, 3600, function () {
            $tools = Tool::active()
                ->orderBy('views_count', 'desc')
                ->orderBy('uses_count', 'desc')
                ->limit(20)
                ->get();
            
            return [
                'score' => $tools->count() > 0 ? 0.5 : 0.1,
                'tools' => $tools,
            ];
        });
    }
    
    /**
     * সিমিলার টুলস
     */
    private function getSimilarTools(array $context): array
    {
        if (!isset($context['tool_id'])) {
            return ['score' => 0, 'tools' => []];
        }
        
        $tool = Tool::find($context['tool_id']);
        if (!$tool) {
            return ['score' => 0, 'tools' => []];
        }
        
        $similarTools = $tool->getRelatedTools(10);
        
        return [
            'score' => $similarTools->count() > 0 ? 0.6 : 0.1,
            'tools' => $similarTools,
        ];
    }
    
    /**
     * অ্যাফিনিটি টুলস
     */
    private function getAffinityTools($userId): array
    {
        if (!$userId) {
            return ['score' => 0, 'tools' => []];
        }
        
        // ইউজারের ফেভারিট টুলস
        $favoriteTools = UserActivity::where('user_id', $userId)
            ->where('action', 'tool_favorited')
            ->pluck('tool_id');
        
        if ($favoriteTools->isEmpty()) {
            return ['score' => 0, 'tools' => []];
        }
        
        // ফেভারিট টুলসের ক্যাটাগরি
        $categories = Tool::whereIn('id', $favoriteTools)
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->pluck('id')
            ->unique();
        
        $affinityTools = Tool::active()
            ->whereHas('categories', function ($query) use ($categories) {
                $query->whereIn('categories.id', $categories);
            })
            ->whereNotIn('id', $favoriteTools)
            ->limit(15)
            ->get();
        
        return [
            'score' => $affinityTools->count() > 0 ? 0.7 : 0.1,
            'tools' => $affinityTools,
        ];
    }
    
    /**
     * সাজেশন র‌্যাংকিং
     */
    private function rankSuggestions(array $suggestions): Collection
    {
        $rankedTools = [];
        $toolScores = [];
        
        foreach ($suggestions as $source => $suggestion) {
            $score = $suggestion['score'] ?? 0;
            $tools = $suggestion['tools'] ?? [];
            
            foreach ($tools as $tool) {
                $toolId = $tool->id;
                
                if (!isset($toolScores[$toolId])) {
                    $toolScores[$toolId] = [
                        'tool' => $tool,
                        'total_score' => 0,
                        'sources' => [],
                    ];
                }
                
                $toolScores[$toolId]['total_score'] += $score;
                $toolScores[$toolId]['sources'][] = $source;
            }
        }
        
        // স্কোর অনুযায়ী সর্ট
        usort($toolScores, function ($a, $b) {
            return $b['total_score'] - $a['total_score'];
        });
        
        // টপ ২০ টুলস
        $rankedTools = array_slice($toolScores, 0, 20);
        
        return collect($rankedTools);
    }
    
    /**
     * ইনটেন্ট ডিটেকশন
     */
    private function detectIntent(string $query): array
    {
        $intentTypes = [
            'calculate' => ['calculate', 'math', 'convert', 'calculator'],
            'edit' => ['edit', 'modify', 'change', 'update'],
            'create' => ['create', 'generate', 'make', 'build'],
            'analyze' => ['analyze', 'check', 'test', 'inspect'],
            'convert' => ['convert', 'transform', 'change'],
            'compress' => ['compress', 'reduce', 'minimize'],
            'download' => ['download', 'save', 'export'],
            'upload' => ['upload', 'share', 'send'],
        ];
        
        $queryLower = Str::lower($query);
        
        foreach ($intentTypes as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($queryLower, $keyword)) {
                    return [
                        'type' => $type,
                        'confidence' => 0.9,
                    ];
                }
            }
        }
        
        return [
            'type' => 'general',
            'confidence' => 0.3,
        ];
    }
    
    /**
     * ক্যাটাগরি প্রেফারেন্স
     */
    private function getCategoryPreferences(Collection $activities): array
    {
        $categoryCount = [];
        
        foreach ($activities as $activity) {
            if ($activity->tool) {
                foreach ($activity->tool->categories as $category) {
                    $categoryCount[$category->id] = ($categoryCount[$category->id] ?? 0) + 1;
                }
            }
        }
        
        arsort($categoryCount);
        
        return array_slice($categoryCount, 0, 5, true);
    }
    
    /**
     * ইউজার ক্যাটাগরি আইডি
     */
    private function getUserCategoryIds($userId): array
    {
        return UserActivity::where('user_id', $userId)
            ->where('action', 'tool_used')
            ->with('tool.categories')
            ->get()
            ->pluck('tool.categories')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();
    }
    
    /**
     * কনফিডেন্স ক্যালকুলেশন
     */
    private function calculateConfidence(float $score): float
    {
        return min(1, max(0, $score));
    }
    
    /**
     * সিজন নির্ধারণ
     */
    private function getSeason(int $month): string
    {
        return match (true) {
            in_array($month, [3, 4, 5]) => 'spring',
            in_array($month, [6, 7, 8]) => 'summer',
            in_array($month, [9, 10, 11]) => 'autumn',
            default => 'winter',
        };
    }
}
