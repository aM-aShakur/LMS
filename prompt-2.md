# LMS Architecture: Following Eskil Steenberg's Principles

## Core Philosophy Application

### 1. Technology Footprint (Small & Proven)
**Primary Stack:**
- **Laravel 11** (proven, stable, lasting framework)
- **MySQL 8.0** (proven database, will last forever)
- **Redis** (proven caching, simple)
- **Nginx** (proven web server)
- **PHP 8.3** (stable, proven)

**Minimal Dependencies:**
- Only essential Laravel packages
- No experimental or bleeding-edge libraries
- Each dependency must be justified and proven

### 2. Simple is Good - Core Module Structure

```
app/
├── Modules/                    # Modular architecture
│   ├── Core/                  # Essential system functions
│   │   ├── User/
│   │   ├── Auth/
│   │   └── System/
│   ├── Learning/              # Learning-specific modules
│   │   ├── Course/
│   │   ├── Content/
│   │   ├── Progress/
│   │   └── Assessment/
│   ├── Communication/         # Communication modules
│   │   ├── Discussion/
│   │   ├── Message/
│   │   └── Notification/
│   └── Enterprise/            # Future multi-tenant features
│       ├── Tenant/
│       ├── Billing/
│       └── Integration/
└── Foundation/                # Base classes, never changes
    ├── ModuleServiceProvider.php
    ├── ModuleRepository.php
    └── ModuleController.php
```

### 3. Explicit Over Clever

**Clear Naming Convention:**
```php
// Following Steenberg's principles:
class CourseContentCreate          // ModuleObjectAction pattern
class CourseContentDestroy
class CourseContentUpdate
class CourseContentGet

// Instead of clever shortcuts:
class CourseManager               // Too vague
class ContentHandler             // What does it handle?
```

**Explicit Database Queries:**
```php
// Good: Explicit and clear
public function UserCourseEnrollmentsByStatus(User $user, string $status): Collection
{
    return DB::table('course_enrollments')
        ->where('user_id', $user->id)
        ->where('status', $status)
        ->get();
}

// Bad: Too clever, hidden complexity
public function getUserStuff($user, $type) { ... }
```

### 4. Sequential Code Architecture

**Long, Linear Controllers:**
```php
class CourseEnrollmentController extends Controller 
{
    public function CourseEnrollmentCreate(Request $request): Response
    {
        // All logic in sequence, no jumping around
        $user = $this->UserAuthenticatedGet();
        $course = $this->CourseByIdGet($request->course_id);
        
        $this->CourseEnrollmentValidate($user, $course);
        $this->CourseCapacityCheck($course);
        $this->UserPrerequisitesVerify($user, $course);
        
        $enrollment = $this->DatabaseEnrollmentCreate($user, $course);
        $this->UserProgressInitialize($enrollment);
        $this->NotificationEnrollmentSend($user, $course);
        
        return $this->ResponseSuccessCreate($enrollment);
    }
}
```

### 5. Module System (Plugin Architecture)

**Base Module Structure:**
```php
abstract class LMSModuleBase
{
    protected string $module_name;
    protected array $module_dependencies = [];
    protected bool $module_required = false;
    
    abstract public function ModuleInstall(): bool;
    abstract public function ModuleUninstall(): bool;
    abstract public function ModuleConfigGet(): array;
    
    final public function ModuleDependenciesCheck(): bool
    {
        foreach($this->module_dependencies as $dependency) {
            if(!$this->ModuleInstalledCheck($dependency)) {
                return false;
            }
        }
        return true;
    }
}
```

## Database Architecture (Simple & Explicit)

### Core Tables (Never Change)
```sql
-- User management (explicit, simple)
users
├── id (bigint, primary)
├── email (varchar, unique)  
├── password_hash (varchar)
├── first_name (varchar)
├── last_name (varchar)
├── status (enum: active, inactive, suspended)
├── created_at (timestamp)
└── updated_at (timestamp)

-- Course structure (explicit relationships)
courses
├── id (bigint, primary)
├── tenant_id (bigint, nullable for future)
├── title (varchar)
├── description (text)
├── status (enum: draft, published, archived)
├── max_enrollments (int)
├── created_by (bigint, foreign to users)
├── created_at (timestamp)
└── updated_at (timestamp)

-- Enrollment tracking (simple state machine)
course_enrollments  
├── id (bigint, primary)
├── user_id (bigint, foreign)
├── course_id (bigint, foreign)
├── status (enum: enrolled, completed, dropped, failed)
├── enrolled_at (timestamp)
├── completed_at (timestamp, nullable)
├── progress_percentage (tinyint, 0-100)
└── last_activity_at (timestamp)
```

### Modular Tables (Can be added/removed)
```sql
-- Content delivery
course_content
├── id (bigint, primary)
├── course_id (bigint, foreign)
├── parent_id (bigint, nullable, self-reference)
├── title (varchar)
├── content_type (enum: text, video, quiz, file)
├── content_data (json)
├── sort_order (int)
├── is_required (boolean)
├── created_at (timestamp)
└── updated_at (timestamp)

-- Assessment system  
assessments
├── id (bigint, primary)
├── course_id (bigint, foreign)
├── title (varchar)
├── assessment_type (enum: quiz, assignment, exam)
├── max_attempts (int)
├── time_limit_minutes (int, nullable)
├── passing_score (int)
├── created_at (timestamp)
└── updated_at (timestamp)
```

## Performance Architecture (5000 Concurrent Users)

### 1. Database Optimization
```php
// Following Steenberg: Arrays better than linked lists
// Batch operations instead of N+1 queries

class UserProgressBatchUpdate
{
    public function UserProgressBatchUpdateExecute(array $user_course_progress): void
    {
        // Single query instead of multiple
        $cases_progress = [];
        $cases_updated = [];
        $ids = [];
        
        foreach($user_course_progress as $item) {
            $cases_progress[] = "WHEN {$item['enrollment_id']} THEN {$item['progress']}";
            $cases_updated[] = "WHEN {$item['enrollment_id']} THEN NOW()";
            $ids[] = $item['enrollment_id'];
        }
        
        $ids_string = implode(',', $ids);
        $progress_case = implode(' ', $cases_progress);
        $updated_case = implode(' ', $cases_updated);
        
        DB::statement("
            UPDATE course_enrollments 
            SET progress_percentage = CASE id {$progress_case} END,
                last_activity_at = CASE id {$updated_case} END
            WHERE id IN ({$ids_string})
        ");
    }
}
```

### 2. Caching Strategy (Simple & Explicit)
```php
class CourseCacheManager
{
    private const CACHE_TTL_COURSE_LIST = 3600;        // 1 hour
    private const CACHE_TTL_COURSE_CONTENT = 7200;     // 2 hours
    private const CACHE_TTL_USER_PROGRESS = 300;       // 5 minutes
    
    public function CourseListByUserGet(User $user): Collection
    {
        $cache_key = "user_courses_{$user->id}";
        
        return Cache::remember($cache_key, self::CACHE_TTL_COURSE_LIST, function() use ($user) {
            return $this->CourseListByUserFromDatabase($user);
        });
    }
    
    public function CourseCacheInvalidate(int $course_id): void
    {
        // Explicit cache clearing, no magic
        Cache::forget("course_{$course_id}");
        Cache::forget("course_content_{$course_id}");
        
        // Clear user caches that might contain this course
        $enrollments = DB::table('course_enrollments')
            ->where('course_id', $course_id)
            ->pluck('user_id');
            
        foreach($enrollments as $user_id) {
            Cache::forget("user_courses_{$user_id}");
        }
    }
}
```

### 3. Queue System (Handle Peak Loads)
```php
// Simple job structure
class UserEnrollmentProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private int $user_id;
    private int $course_id;
    
    public function __construct(int $user_id, int $course_id)
    {
        $this->user_id = $user_id;
        $this->course_id = $course_id;
    }
    
    public function handle(): void
    {
        // Sequential processing, no magic
        $user = User::find($this->user_id);
        $course = Course::find($this->course_id);
        
        $this->EnrollmentCreate($user, $course);
        $this->ProgressInitialize($user, $course);  
        $this->NotificationSend($user, $course);
    }
}
```

## Multi-Tenant Architecture (Future-Proof)

### 1. Database Strategy
```php
// Single database, tenant_id column approach (simple)
Schema::table('courses', function (Blueprint $table) {
    $table->bigInteger('tenant_id')->nullable()->index();
});

// Middleware to filter by tenant
class TenantScopeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($tenant_id = $this->TenantIdFromRequest($request)) {
            // Simple global scope, explicit filtering
            DB::listen(function ($query) use ($tenant_id) {
                if ($this->QueryNeedsTenantFilter($query->sql)) {
                    // Log if query doesn't have tenant filter - catch bugs
                    Log::warning('Query without tenant filter', ['sql' => $query->sql]);
                }
            });
        }
        
        return $next($request);
    }
}
```

### 2. Module Loading by Tenant
```php
class TenantModuleManager
{
    public function ModulesForTenantLoad(int $tenant_id): array
    {
        // Explicit module loading
        $enabled_modules = DB::table('tenant_modules')
            ->where('tenant_id', $tenant_id)
            ->where('is_enabled', true)
            ->pluck('module_name')
            ->toArray();
            
        $loaded_modules = [];
        foreach($enabled_modules as $module_name) {
            $module_class = "App\\Modules\\{$module_name}\\{$module_name}Module";
            if(class_exists($module_class)) {
                $loaded_modules[] = new $module_class();
            }
        }
        
        return $loaded_modules;
    }
}
```

## Development Team Structure (3 Developers)

### 1. Module Ownership
- **Senior Developer**: Core modules (User, Auth, System, Database)  
- **Junior Developer 1**: Learning modules (Course, Content, Assessment)
- **Junior Developer 2**: Communication modules (Discussion, Message, Notification)

### 2. Code Standards (Steenberg Principles)
```php
// Naming convention (explicit and searchable)
class UserAuthenticationValidate           // Clear purpose
class CourseContentByIdGet                // Clear action
class AssessmentResultCalculate           // Clear function

// Function structure (long and sequential)  
public function CourseEnrollmentProcessComplete(User $user, Course $course): EnrollmentResult
{
    // Step 1: Validate prerequisites
    $prerequisites_met = $this->CoursePrerequisitesValidate($user, $course);
    if(!$prerequisites_met) {
        return EnrollmentResult::createFailure('Prerequisites not met');
    }
    
    // Step 2: Check capacity
    $capacity_available = $this->CourseCapacityCheck($course);  
    if(!$capacity_available) {
        return EnrollmentResult::createFailure('Course full');
    }
    
    // Step 3: Create enrollment record
    $enrollment = $this->DatabaseEnrollmentCreate($user, $course);
    
    // Step 4: Initialize progress tracking
    $progress = $this->UserProgressInitialize($enrollment);
    
    // Step 5: Send notifications
    $this->NotificationEnrollmentSend($user, $course);
    
    // Step 6: Update course statistics
    $this->CourseStatisticsUpdate($course);
    
    return EnrollmentResult::createSuccess($enrollment);
}
```

## Deployment & Infrastructure

### 1. Simple Stack
```yaml
# Docker Compose (simple, proven)
version: '3.8'
services:
  app:
    build: .
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
      
  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
    volumes:
      - mysql_data:/var/lib/mysql
      
  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
      
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
```

### 2. Scaling Strategy (5000 Users)
- **Application Servers**: 3 instances behind load balancer
- **Database**: Master-slave setup with read replicas
- **Cache**: Redis cluster for session and data caching  
- **Queue Workers**: Separate containers for background processing

## Timeline (7 Months Internal)

### Month 1-2: Foundation
- Core module structure
- User authentication system
- Basic course management
- Database schema

### Month 3-4: Learning Features  
- Content delivery system
- Progress tracking
- Assessment framework
- Communication tools

### Month 5-6: Performance & Polish
- Caching implementation
- Performance optimization  
- Testing and bug fixes
- User interface improvements

### Month 7: Deployment & Testing
- Production deployment
- Load testing (5000 users)
- Internal user training
- Documentation

## Future Enterprise Features (Month 8+)

### Multi-Tenancy
- Tenant management system
- Isolated data and configurations
- Custom branding per tenant
- Billing and subscription management

### Integration APIs
- RESTful API for third-party integrations
- SSO (SAML, OAuth) support
- LTI compliance for external tools
- Webhook system for real-time updates

### Advanced Analytics
- Learning analytics dashboard
- Progress reporting
- Performance insights
- Custom report builder

This architecture follows Eskil Steenberg's principles religiously:
- **Small technology footprint** with proven tools
- **Simple over clever** with explicit code
- **Sequential processing** with long, clear functions  
- **Modular design** that can grow
- **Future-proof** architecture for enterprise sales
- **Performance-focused** for 5000 concurrent users
- **Maintainable** by a 3-person team