<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sss_contributions')) {
            Schema::create('sss_contributions', function (Blueprint $table) {
                $table->id();
                $table->decimal('min_salary', 10, 2);
                $table->decimal('max_salary', 10, 2);
                $table->decimal('employee_share', 10, 2);
                $table->decimal('employer_share', 10, 2);
                $table->year('effectivity_year');
                $table->timestamps();
            });

            // Insert default SSS data
            $sssData = [
                [0, 3250, 135.00, 270.00],
                [3250.01, 3750, 157.50, 315.00],
                [3750.01, 4250, 180.00, 360.00],
                [4250.01, 4750, 202.50, 405.00],
                [4750.01, 5250, 225.00, 450.00],
                [5250.01, 5750, 247.50, 495.00],
                [5750.01, 6250, 270.00, 540.00],
                [6250.01, 6750, 292.50, 585.00],
                [6750.01, 7250, 315.00, 630.00],
                [7250.01, 7750, 337.50, 675.00],
                [7750.01, 8250, 360.00, 720.00],
                [8250.01, 8750, 382.50, 765.00],
                [8750.01, 9250, 405.00, 810.00],
                [9250.01, 9750, 427.50, 855.00],
                [9750.01, 10250, 450.00, 900.00],
                [10250.01, 10750, 472.50, 945.00],
                [10750.01, 11250, 495.00, 990.00],
                [11250.01, 11750, 517.50, 1035.00],
                [11750.01, 12250, 540.00, 1080.00],
                [12250.01, 12750, 562.50, 1125.00],
                [12750.01, 13250, 585.00, 1170.00],
                [13250.01, 13750, 607.50, 1215.00],
                [13750.01, 14250, 630.00, 1260.00],
                [14250.01, 14750, 652.50, 1305.00],
                [14750.01, 15250, 675.00, 1350.00],
                [15250.01, 15750, 697.50, 1395.00],
                [15750.01, 16250, 720.00, 1440.00],
                [16250.01, 16750, 742.50, 1485.00],
                [16750.01, 17250, 765.00, 1530.00],
                [17250.01, 17750, 787.50, 1575.00],
                [17750.01, 18250, 810.00, 1620.00],
                [18250.01, 18750, 832.50, 1665.00],
                [18750.01, 19250, 855.00, 1710.00],
                [19250.01, 19750, 877.50, 1755.00],
                [19750.01, 20250, 900.00, 1800.00],
            ];

            foreach ($sssData as $data) {
                DB::table('sss_contributions')->insert([
                    'min_salary' => $data[0],
                    'max_salary' => $data[1],
                    'employee_share' => $data[2],
                    'employer_share' => $data[3],
                    'effectivity_year' => 2024,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('sss_contributions');
    }
};
