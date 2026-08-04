<?php

namespace App\Enums;

enum SyllabusMaterialSection: string
{
    case CourseDescription = 'course_description';
    case ClassPolicies = 'class_policies';
    case SubmissionAndCollection = 'submission_and_collection';
    case TutorialActivityPlan = 'tutorial_activity_plan';
    case LearningOutcomes = 'learning_outcomes';
    case Evaluation = 'evaluation';
    case AssessmentRubric = 'assessment_rubric';
    case TeachingLearningStrategies = 'teaching_learning_strategies';
    case Textbooks = 'textbooks';
    case CompetencyMap = 'competency_map';
    case VideoOverview = 'video_overview';
}
