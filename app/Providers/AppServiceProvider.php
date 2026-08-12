<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Enums\TierFeature;
use App\Http\Responses\CustomAuthenticatedSessionResponse;
use App\Http\Responses\CustomVerifyEmailViewResponse;
use App\Listeners\UpdateUserTimezoneOnLogin;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Repositories\Assessment\AssessmentRepository;
use App\Repositories\Assessment\AssessmentRepositoryInterface;
use App\Repositories\AssessmentAnswer\AssessmentAnswerRepository;
use App\Repositories\AssessmentAnswer\AssessmentAnswerRepositoryInterface;
use App\Repositories\AssessmentAttempt\AssessmentAttemptRepository;
use App\Repositories\AssessmentAttempt\AssessmentAttemptRepositoryInterface;
use App\Repositories\AssessmentQuestion\AssessmentQuestionRepository;
use App\Repositories\AssessmentQuestion\AssessmentQuestionRepositoryInterface;
use App\Repositories\AssessmentQuestionScore\AssessmentQuestionScoreRepository;
use App\Repositories\AssessmentQuestionScore\AssessmentQuestionScoreRepositoryInterface;
use App\Repositories\AssessmentQuizAnswer\AssessmentQuizAnswerRepository;
use App\Repositories\AssessmentQuizAnswer\AssessmentQuizAnswerRepositoryInterface;
use App\Repositories\AssessmentScore\AssessmentScoreRepository;
use App\Repositories\AssessmentScore\AssessmentScoreRepositoryInterface;
use App\Repositories\Attendance\AttendanceRepository;
use App\Repositories\Attendance\AttendanceRepositoryInterface;
use App\Repositories\AttendanceRequirement\AttendanceRequirementRepository;
use App\Repositories\AttendanceRequirement\AttendanceRequirementRepositoryInterface;
use App\Repositories\Auth\AuthRepository;
use App\Repositories\Auth\AuthRepositoryInterface;
use App\Repositories\Course\CourseRepository;
use App\Repositories\Course\CourseRepositoryInterface;
use App\Repositories\CourseAttendanceSetting\CourseAttendanceSettingRepository;
use App\Repositories\CourseAttendanceSetting\CourseAttendanceSettingRepositoryInterface;
use App\Repositories\CoursePerson\CoursePersonRepository;
use App\Repositories\CoursePerson\CoursePersonRepositoryInterface;
use App\Repositories\DemoLmsAccess\DemoLmsAccessRepository;
use App\Repositories\DemoLmsAccess\DemoLmsAccessRepositoryInterface;
use App\Repositories\FinalExam\FinalExamRepository;
use App\Repositories\FinalExam\FinalExamRepositoryInterface;
use App\Repositories\Forum\ForumRepository;
use App\Repositories\Forum\ForumRepositoryInterface;
use App\Repositories\ForumComment\ForumCommentRepository;
use App\Repositories\ForumComment\ForumCommentRepositoryInterface;
use App\Repositories\ForumCommentLike\ForumCommentLikeRepository;
use App\Repositories\ForumCommentLike\ForumCommentLikeRepositoryInterface;
use App\Repositories\ForumThread\ForumThreadRepository;
use App\Repositories\ForumThread\ForumThreadRepositoryInterface;
use App\Repositories\ForumThreadRead\ForumThreadReadRepository;
use App\Repositories\ForumThreadRead\ForumThreadReadRepositoryInterface;
use App\Repositories\GradebookEntry\GradebookEntryRepository;
use App\Repositories\GradebookEntry\GradebookEntryRepositoryInterface;
use App\Repositories\GradebookGradeScale\GradebookGradeScaleRepository;
use App\Repositories\GradebookGradeScale\GradebookGradeScaleRepositoryInterface;
use App\Repositories\GradebookSessionEntry\GradebookSessionEntryRepository;
use App\Repositories\GradebookSessionEntry\GradebookSessionEntryRepositoryInterface;
use App\Repositories\Group\GroupRepository;
use App\Repositories\Group\GroupRepositoryInterface;
use App\Repositories\GroupMember\GroupMemberRepository;
use App\Repositories\GroupMember\GroupMemberRepositoryInterface;
use App\Repositories\MediaLibrary\MediaLibraryRepository;
use App\Repositories\MediaLibrary\MediaLibraryRepositoryInterface;
use App\Repositories\PaymentGateway\PaymentGatewayRepository;
use App\Repositories\PaymentGateway\PaymentGatewayRepositoryInterface;
use App\Repositories\PaymentGatewayCredential\PaymentGatewayCredentialRepository;
use App\Repositories\PaymentGatewayCredential\PaymentGatewayCredentialRepositoryInterface;
use App\Repositories\PaymentGatewayTestTransaction\PaymentGatewayTestTransactionRepository;
use App\Repositories\PaymentGatewayTestTransaction\PaymentGatewayTestTransactionRepositoryInterface;
use App\Repositories\PaymentGatewayType\PaymentGatewayTypeRepository;
use App\Repositories\PaymentGatewayType\PaymentGatewayTypeRepositoryInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionRepository;
use App\Repositories\PaymentTransaction\PaymentTransactionRepositoryInterface;
use App\Repositories\PaymentWebhook\PaymentWebhookRepository;
use App\Repositories\PaymentWebhook\PaymentWebhookRepositoryInterface;
use App\Repositories\Period\PeriodRepository;
use App\Repositories\Period\PeriodRepositoryInterface;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Permission\PermissionRepositoryInterface;
use App\Repositories\PricingTier\PricingTierRepository;
use App\Repositories\PricingTier\PricingTierRepositoryInterface;
use App\Repositories\ProctorEvent\ProctorEventRepository;
use App\Repositories\ProctorEvent\ProctorEventRepositoryInterface;
use App\Repositories\ProctorSession\ProctorSessionRepository;
use App\Repositories\ProctorSession\ProctorSessionRepositoryInterface;
use App\Repositories\ProctorSnapshot\ProctorSnapshotRepository;
use App\Repositories\ProctorSnapshot\ProctorSnapshotRepositoryInterface;
use App\Repositories\Quiz\QuizRepository;
use App\Repositories\Quiz\QuizRepositoryInterface;
use App\Repositories\QuizInstruction\QuizInstructionRepository;
use App\Repositories\QuizInstruction\QuizInstructionRepositoryInterface;
use App\Repositories\QuizQuestion\QuizQuestionRepository;
use App\Repositories\QuizQuestion\QuizQuestionRepositoryInterface;
use App\Repositories\QuizQuestionOption\QuizQuestionOptionRepository;
use App\Repositories\QuizQuestionOption\QuizQuestionOptionRepositoryInterface;
use App\Repositories\Role\RoleRepository;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\School\SchoolRepository;
use App\Repositories\School\SchoolRepositoryInterface;
use App\Repositories\SchoolTier\SchoolTierRepository;
use App\Repositories\SchoolTier\SchoolTierRepositoryInterface;
use App\Repositories\Session\SessionRepository;
use App\Repositories\Session\SessionRepositoryInterface;
use App\Repositories\SessionMaterialCompletion\SessionMaterialCompletionRepository;
use App\Repositories\SessionMaterialCompletion\SessionMaterialCompletionRepositoryInterface;
use App\Repositories\SessionProgress\SessionProgressRepository;
use App\Repositories\SessionProgress\SessionProgressRepositoryInterface;
use App\Repositories\SessionSubtopic\SessionSubtopicRepository;
use App\Repositories\SessionSubtopic\SessionSubtopicRepositoryInterface;
use App\Repositories\StorageUsageLog\StorageUsageLogRepository;
use App\Repositories\StorageUsageLog\StorageUsageLogRepositoryInterface;
use App\Repositories\Syllabus\SyllabusRepository;
use App\Repositories\Syllabus\SyllabusRepositoryInterface;
use App\Repositories\SyllabusClassPolicy\SyllabusClassPolicyRepository;
use App\Repositories\SyllabusClassPolicy\SyllabusClassPolicyRepositoryInterface;
use App\Repositories\SyllabusEvaluation\SyllabusEvaluationRepository;
use App\Repositories\SyllabusEvaluation\SyllabusEvaluationRepositoryInterface;
use App\Repositories\SyllabusEvaluationActivity\SyllabusEvaluationActivityRepository;
use App\Repositories\SyllabusEvaluationActivity\SyllabusEvaluationActivityRepositoryInterface;
use App\Repositories\SyllabusLearningOutcome\SyllabusLearningOutcomeRepository;
use App\Repositories\SyllabusLearningOutcome\SyllabusLearningOutcomeRepositoryInterface;
use App\Repositories\SyllabusRubricCell\SyllabusRubricCellRepository;
use App\Repositories\SyllabusRubricCell\SyllabusRubricCellRepositoryInterface;
use App\Repositories\SyllabusRubricKeyIndicator\SyllabusRubricKeyIndicatorRepository;
use App\Repositories\SyllabusRubricKeyIndicator\SyllabusRubricKeyIndicatorRepositoryInterface;
use App\Repositories\SyllabusRubricProficiencyLevel\SyllabusRubricProficiencyLevelRepository;
use App\Repositories\SyllabusRubricProficiencyLevel\SyllabusRubricProficiencyLevelRepositoryInterface;
use App\Repositories\TierChange\TierChangeRepository;
use App\Repositories\TierChange\TierChangeRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\UserLoginLink\UserLoginLinkRepository;
use App\Repositories\UserLoginLink\UserLoginLinkRepositoryInterface;
use App\Repositories\VideoConference\VideoConferenceRepository;
use App\Repositories\VideoConference\VideoConferenceRepositoryInterface;
use App\Repositories\VideoConferenceParticipation\VideoConferenceParticipationRepository;
use App\Repositories\VideoConferenceParticipation\VideoConferenceParticipationRepositoryInterface;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentQuizAnswerService;
use App\Services\AssessmentScoreService;
use App\Services\AssessmentService;
use App\Services\AttendanceRequirementService;
use App\Services\AttendanceService;
use App\Services\CourseAttendanceSettingService;
use App\Services\CoursePersonService;
use App\Services\CourseService;
use App\Services\CredentialEncryption;
use App\Services\FeatureGateService;
use App\Services\FinalExamService;
use App\Services\ForumCommentLikeService;
use App\Services\ForumCommentService;
use App\Services\ForumService;
use App\Services\ForumThreadReadService;
use App\Services\ForumThreadService;
use App\Services\GradebookEntryService;
use App\Services\GradebookGradeScaleService;
use App\Services\GradebookSessionEntryService;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use App\Services\PaymentGatewayConfigService;
use App\Services\PaymentGatewayFactory;
use App\Services\PaymentGatewayRegistry;
use App\Services\PaymentWebhookService;
use App\Services\PeriodService;
use App\Services\PricingTierService;
use App\Services\ProctorEventService;
use App\Services\ProctorSessionService;
use App\Services\ProctorSnapshotService;
use App\Services\QuizInstructionService;
use App\Services\QuizQuestionOptionService;
use App\Services\QuizQuestionService;
use App\Services\QuizService;
use App\Services\R2StorageService;
use App\Services\SchoolService;
use App\Services\SessionProgressService;
use App\Services\SessionService;
use App\Services\SessionSubtopicService;
use App\Services\SubscriptionPaymentService;
use App\Services\SyllabusClassPolicyService;
use App\Services\SyllabusEvaluationActivityService;
use App\Services\SyllabusEvaluationService;
use App\Services\SyllabusLearningOutcomeService;
use App\Services\SyllabusRubricCellService;
use App\Services\SyllabusRubricKeyIndicatorService;
use App\Services\SyllabusRubricProficiencyLevelService;
use App\Services\SyllabusService;
use App\Services\TierChangeService;
use App\Services\VideoConferenceParticipationService;
use App\Services\VideoConferenceService;
use App\Support\CurrentSchool;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VerifyEmailViewResponse::class, function () {
            return new CustomVerifyEmailViewResponse;
        });

        $this->app->singleton(LoginResponse::class, function () {
            return new CustomAuthenticatedSessionResponse;
        });

        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(PricingTierRepositoryInterface::class, PricingTierRepository::class);
        $this->app->bind(SchoolRepositoryInterface::class, SchoolRepository::class);
        $this->app->bind(PaymentGatewayRepositoryInterface::class, PaymentGatewayRepository::class);
        $this->app->bind(CourseRepositoryInterface::class, CourseRepository::class);
        $this->app->bind(MediaLibraryRepositoryInterface::class, MediaLibraryRepository::class);
        $this->app->bind(DemoLmsAccessRepositoryInterface::class, DemoLmsAccessRepository::class);
        $this->app->bind(UserLoginLinkRepositoryInterface::class, UserLoginLinkRepository::class);
        $this->app->bind(SchoolTierRepositoryInterface::class, SchoolTierRepository::class);
        $this->app->bind(TierChangeRepositoryInterface::class, TierChangeRepository::class);
        $this->app->bind(PaymentTransactionRepositoryInterface::class, PaymentTransactionRepository::class);
        $this->app->bind(PaymentGatewayTypeRepositoryInterface::class, PaymentGatewayTypeRepository::class);
        $this->app->bind(PaymentWebhookRepositoryInterface::class, PaymentWebhookRepository::class);
        $this->app->bind(PaymentGatewayCredentialRepositoryInterface::class, PaymentGatewayCredentialRepository::class);
        $this->app->bind(PaymentGatewayTestTransactionRepositoryInterface::class, PaymentGatewayTestTransactionRepository::class);
        $this->app->bind(StorageUsageLogRepositoryInterface::class, StorageUsageLogRepository::class);

        $this->app->bind(SessionRepositoryInterface::class, SessionRepository::class);
        $this->app->bind(SessionSubtopicRepositoryInterface::class, SessionSubtopicRepository::class);
        $this->app->bind(SessionMaterialCompletionRepositoryInterface::class, SessionMaterialCompletionRepository::class);
        $this->app->bind(SessionProgressRepositoryInterface::class, SessionProgressRepository::class);
        $this->app->bind(VideoConferenceRepositoryInterface::class, VideoConferenceRepository::class);
        $this->app->bind(VideoConferenceParticipationRepositoryInterface::class, VideoConferenceParticipationRepository::class);
        $this->app->bind(PeriodRepositoryInterface::class, PeriodRepository::class);
        $this->app->bind(SyllabusRepositoryInterface::class, SyllabusRepository::class);
        $this->app->bind(SyllabusClassPolicyRepositoryInterface::class, SyllabusClassPolicyRepository::class);
        $this->app->bind(SyllabusLearningOutcomeRepositoryInterface::class, SyllabusLearningOutcomeRepository::class);
        $this->app->bind(SyllabusEvaluationRepositoryInterface::class, SyllabusEvaluationRepository::class);
        $this->app->bind(SyllabusEvaluationActivityRepositoryInterface::class, SyllabusEvaluationActivityRepository::class);
        $this->app->bind(SyllabusRubricKeyIndicatorRepositoryInterface::class, SyllabusRubricKeyIndicatorRepository::class);
        $this->app->bind(SyllabusRubricProficiencyLevelRepositoryInterface::class, SyllabusRubricProficiencyLevelRepository::class);
        $this->app->bind(SyllabusRubricCellRepositoryInterface::class, SyllabusRubricCellRepository::class);
        $this->app->bind(CoursePersonRepositoryInterface::class, CoursePersonRepository::class);
        $this->app->bind(GroupRepositoryInterface::class, GroupRepository::class);
        $this->app->bind(GroupMemberRepositoryInterface::class, GroupMemberRepository::class);
        $this->app->bind(ForumRepositoryInterface::class, ForumRepository::class);
        $this->app->bind(ForumThreadRepositoryInterface::class, ForumThreadRepository::class);
        $this->app->bind(ForumCommentRepositoryInterface::class, ForumCommentRepository::class);
        $this->app->bind(ForumCommentLikeRepositoryInterface::class, ForumCommentLikeRepository::class);
        $this->app->bind(ForumThreadReadRepositoryInterface::class, ForumThreadReadRepository::class);
        $this->app->bind(AssessmentRepositoryInterface::class, AssessmentRepository::class);
        $this->app->bind(AssessmentAttemptRepositoryInterface::class, AssessmentAttemptRepository::class);
        $this->app->bind(AssessmentScoreRepositoryInterface::class, AssessmentScoreRepository::class);
        $this->app->bind(AssessmentQuestionScoreRepositoryInterface::class, AssessmentQuestionScoreRepository::class);
        $this->app->bind(AssessmentQuestionRepositoryInterface::class, AssessmentQuestionRepository::class);
        $this->app->bind(AssessmentAnswerRepositoryInterface::class, AssessmentAnswerRepository::class);
        $this->app->bind(QuizRepositoryInterface::class, QuizRepository::class);
        $this->app->bind(QuizInstructionRepositoryInterface::class, QuizInstructionRepository::class);
        $this->app->bind(QuizQuestionRepositoryInterface::class, QuizQuestionRepository::class);
        $this->app->bind(QuizQuestionOptionRepositoryInterface::class, QuizQuestionOptionRepository::class);
        $this->app->bind(AssessmentQuizAnswerRepositoryInterface::class, AssessmentQuizAnswerRepository::class);
        $this->app->bind(FinalExamRepositoryInterface::class, FinalExamRepository::class);
        $this->app->bind(ProctorSessionRepositoryInterface::class, ProctorSessionRepository::class);
        $this->app->bind(ProctorEventRepositoryInterface::class, ProctorEventRepository::class);
        $this->app->bind(ProctorSnapshotRepositoryInterface::class, ProctorSnapshotRepository::class);
        $this->app->bind(GradebookGradeScaleRepositoryInterface::class, GradebookGradeScaleRepository::class);
        $this->app->bind(GradebookEntryRepositoryInterface::class, GradebookEntryRepository::class);
        $this->app->bind(GradebookSessionEntryRepositoryInterface::class, GradebookSessionEntryRepository::class);
        $this->app->bind(AttendanceRepositoryInterface::class, AttendanceRepository::class);
        $this->app->bind(AttendanceRequirementRepositoryInterface::class, AttendanceRequirementRepository::class);
        $this->app->bind(CourseAttendanceSettingRepositoryInterface::class, CourseAttendanceSettingRepository::class);

        $this->app->singleton(CurrentSchool::class);

        $this->app->singleton(CredentialEncryption::class);
        $this->app->singleton(FeatureGateService::class);
        $this->app->singleton(PaymentGatewayRegistry::class);
        $this->app->singleton(PaymentGatewayFactory::class);
        $this->app->singleton(PricingTierService::class);
        $this->app->singleton(SchoolService::class);
        $this->app->singleton(SubscriptionPaymentService::class);
        $this->app->singleton(TierChangeService::class);
        $this->app->singleton(PaymentGatewayConfigService::class);
        $this->app->singleton(PaymentWebhookService::class);
        $this->app->singleton(CourseService::class);
        $this->app->singleton(R2StorageService::class);
        $this->app->singleton(SessionService::class);
        $this->app->singleton(SessionProgressService::class);
        $this->app->singleton(SessionSubtopicService::class);
        $this->app->singleton(VideoConferenceService::class);
        $this->app->singleton(VideoConferenceParticipationService::class);
        $this->app->singleton(PeriodService::class);
        $this->app->singleton(SyllabusService::class);
        $this->app->singleton(SyllabusClassPolicyService::class);
        $this->app->singleton(SyllabusLearningOutcomeService::class);
        $this->app->singleton(SyllabusEvaluationService::class);
        $this->app->singleton(SyllabusEvaluationActivityService::class);
        $this->app->singleton(SyllabusRubricKeyIndicatorService::class);
        $this->app->singleton(SyllabusRubricProficiencyLevelService::class);
        $this->app->singleton(SyllabusRubricCellService::class);
        $this->app->singleton(CoursePersonService::class);
        $this->app->singleton(GroupService::class);
        $this->app->singleton(GroupMemberService::class);
        $this->app->singleton(ForumService::class);
        $this->app->singleton(ForumThreadService::class);
        $this->app->singleton(ForumCommentService::class);
        $this->app->singleton(ForumCommentLikeService::class);
        $this->app->singleton(ForumThreadReadService::class);
        $this->app->singleton(AssessmentService::class);
        $this->app->singleton(AssessmentAttemptService::class);
        $this->app->singleton(AssessmentScoreService::class);
        $this->app->singleton(AssessmentQuestionService::class);
        $this->app->singleton(AssessmentAnswerService::class);
        $this->app->singleton(QuizService::class);
        $this->app->singleton(QuizInstructionService::class);
        $this->app->singleton(QuizQuestionService::class);
        $this->app->singleton(QuizQuestionOptionService::class);
        $this->app->singleton(AssessmentQuizAnswerService::class);
        $this->app->singleton(FinalExamService::class);
        $this->app->singleton(ProctorSessionService::class);
        $this->app->singleton(ProctorEventService::class);
        $this->app->singleton(ProctorSnapshotService::class);
        $this->app->singleton(GradebookGradeScaleService::class);
        $this->app->singleton(GradebookEntryService::class);
        $this->app->singleton(GradebookSessionEntryService::class);
        $this->app->singleton(AttendanceService::class);
        $this->app->singleton(AttendanceRequirementService::class);
        $this->app->singleton(CourseAttendanceSettingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::define('permission', fn (User $user, string $permission) => $user->hasPermissionTo($permission));
        Gate::define('role', fn (User $user, string $role) => $user->hasRole($role));

        Gate::define('viewPulse', fn (User $user) => $user->hasRole(RoleName::Admin));

        Livewire::useScriptTagAttributes(['defer' => true]);

        $this->registerFeatureGates();

        Event::listen(Login::class, UpdateUserTimezoneOnLogin::class);
    }

    private function registerFeatureGates(): void
    {
        $featureGate = $this->app->make(FeatureGateService::class);

        foreach (TierFeature::cases() as $feature) {
            Gate::define("use-{$feature->value}", function (User $user) use ($featureGate, $feature) {
                return $featureGate->can($user, $feature);
            });
        }
    }
}
