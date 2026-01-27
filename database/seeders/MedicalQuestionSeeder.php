<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MedicalQuestion;

class MedicalQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            [
                'question_text' => 'Respiratory ailments e.g. tuberculosis, persistent cough, allergies, cigarette smoking related disorders, shortness of breath, asthma',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'question_text' => 'Have you or any of your dependants ever sought counseling or treatment in connection with HIV or AIDS infections or tested positive for HIV or AIDS?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['HIV', 'AIDS'],
                'requires_additional_info' => false,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'question_text' => 'Ear, nose and throat disorders e.g. hearing/speech impairment, ear infections, sinus problems, nasal/throat surgery, tonsils, adenoids, previous nasal injuries, upper airway infections, epistaxis',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'question_text' => 'Do you or any of your dependants have any hereditary disorders, birth defects or congenital conditions?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['hereditary', 'birth defect', 'congenital'],
                'requires_additional_info' => false,
                'order' => 4,
                'is_active' => true,
            ],
            [
                'question_text' => 'Cardiovascular (heart and blood vessels) disorders e.g. high blood pressure, hypertension, varicose veins, palpitations, deep vein thrombosis, low blood pressure',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 5,
                'is_active' => true,
            ],
            [
                'question_text' => 'Have you or any of your dependants ever sought counseling or treatment in connection with sexual transmitted infection e.g. gonorrhoea, syphilis, herpes simplex, Chlamydia',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['STI', 'gonorrhoea', 'syphilis', 'herpes', 'chlamydia'],
                'requires_additional_info' => false,
                'order' => 6,
                'is_active' => true,
            ],
            [
                'question_text' => 'Have you ever had any endoscopic study of the oesophagus, stomach or Colon and/or treatment and diagnosis of gastro-intestinal disorders e.g. recurrent indigestion, heartburn, ulcers, hernia, piles and fissures?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 7,
                'is_active' => true,
            ],
            [
                'question_text' => 'Musculo-skeletal disorders e.g. arthritis, Back problems, gout, and osteoporosis. All joint problems and fractures',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 8,
                'is_active' => true,
            ],
            [
                'question_text' => 'Neurological disorders e.g. epilepsy, Stroke. Brain or spinal cord disorders, Headache, migraine, Paralysis, meningitis',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['epilepsy', 'stroke', 'paralysis', 'meningitis'],
                'requires_additional_info' => false,
                'order' => 9,
                'is_active' => true,
            ],
            [
                'question_text' => 'Do you or any of your dependants have incomplete dental treatment plan, dental implants, orthodontic treatment, dentures, braces and wisdom teeth problems or do you or any of your dependants currently receive, or expect to receive dental treatment in the next 12 months?',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 10,
                'is_active' => true,
            ],
            [
                'question_text' => 'Psychological disorders e.g. alcohol or drug dependency, anxiety disorder, insomnia, depression, stress, attention deficit disorder, post-traumatic stress, attempted suicide, bipolar disorder',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['suicide', 'bipolar', 'drug dependency'],
                'requires_additional_info' => false,
                'order' => 11,
                'is_active' => true,
            ],
            [
                'question_text' => 'State whether you or any of your dependants have received medical advice or treatment for any tropical disease e.g. leprosy, sleeping sickness, elephantiasis, bilharzia, yellow fever',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['leprosy', 'sleeping sickness', 'elephantiasis', 'bilharzia', 'yellow fever'],
                'requires_additional_info' => false,
                'order' => 12,
                'is_active' => true,
            ],
            [
                'question_text' => 'Gynecological and obstetrical disorders e.g. Fibroids, ectopic pregnancy, caesarian section, Menstrual irregularities. Abnormal pap smear, receiving hormone treatment. Uterine bleeding, Laparoscopic surgery, Dilatation and curettage, miscarriages, pregnancy related problems.',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 13,
                'is_active' => true,
            ],
            [
                'question_text' => 'Pregnant, if positive, provide expected date of delivery (dd/mm/yy)',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => true,
                'additional_info_type' => 'date',
                'additional_info_label' => 'Expected Date of Delivery',
                'order' => 14,
                'is_active' => true,
            ],
            [
                'question_text' => 'Respiratory disorders e.g. asthma, rhinitis, chronic bronchitis, cigarette smoking related disorders, tuberculosis, persistent cough, allergies, chronic obstruction pulmonary disease, shortness of breath.',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 15,
                'is_active' => true,
            ],
            [
                'question_text' => 'Endocrine disorders e.g. diabetes, high cholesterol, thyroid abnormalities',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['diabetes'],
                'requires_additional_info' => false,
                'order' => 16,
                'is_active' => true,
            ],
            [
                'question_text' => 'Skin disorders e.g. eczema, melanoma, skin cancer, burns, scars, keloids',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['melanoma', 'skin cancer', 'cancer'],
                'requires_additional_info' => false,
                'order' => 17,
                'is_active' => true,
            ],
            [
                'question_text' => 'Genital-urinary system e.g. Pelvic inflammatory disease prostate problem, abnormalities of the penis, scrotum. Reproductive system, blood in the urine, kidney stones, kidney failure, bladder problems, Dialysis.',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['kidney failure', 'dialysis'],
                'requires_additional_info' => false,
                'order' => 18,
                'is_active' => true,
            ],
            [
                'question_text' => 'Investigations and/or specialized treatment: In and out of hospital',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 19,
                'is_active' => true,
            ],
            [
                'question_text' => 'Cancer, growths or tumors whether benign or malignant',
                'question_type' => 'yes_no',
                'has_exclusion_list' => true,
                'exclusion_keywords' => ['cancer', 'tumor', 'tumour', 'malignant'],
                'requires_additional_info' => false,
                'order' => 20,
                'is_active' => true,
            ],
            [
                'question_text' => 'Eye related disorders e.g. blindness, glaucoma, eye surgery, cataracts, lens implants, refractive and laser surgery',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => false,
                'order' => 21,
                'is_active' => true,
            ],
            [
                'question_text' => 'Are you or any of your dependants on regular medication? If your response is "YES", please indicate the details as required below:',
                'question_type' => 'yes_no',
                'has_exclusion_list' => false,
                'exclusion_keywords' => [],
                'requires_additional_info' => true,
                'additional_info_type' => 'table',
                'additional_info_label' => 'Medication Details',
                'order' => 22,
                'is_active' => true,
            ],
        ];

        foreach ($questions as $questionData) {
            MedicalQuestion::create($questionData);
        }
    }
}
