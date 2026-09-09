&lt;!DOCTYPE html&gt;
&lt;html lang="en"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;
    &lt;title&gt;Attendance Recorded&lt;/title&gt;
    &lt;style&gt;
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .attendance-details { background-color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    &lt;/style&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;div class="container"&gt;
        &lt;div class="header"&gt;
            &lt;h1&gt;Attendance Recorded&lt;/h1&gt;
        &lt;/div&gt;

        &lt;div class="content"&gt;
            &lt;p&gt;Dear {{ $employeeProfile-&gt;first_name }} {{ $employeeProfile-&gt;last_name }},&lt;/p&gt;

            &lt;p&gt;Your attendance has been successfully recorded.&lt;/p&gt;

            &lt;div class="attendance-details"&gt;
                &lt;h3&gt;Attendance Details:&lt;/h3&gt;
                &lt;p&gt;&lt;strong&gt;Type:&lt;/strong&gt; {{ ucwords(str_replace('_', ' ', $attendanceType)) }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Date:&lt;/strong&gt; {{ $attendanceLog-&gt;attendance_date-&gt;format('F d, Y') }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Time:&lt;/strong&gt;
                    @switch($attendanceType)
                        @case('am_in')
                            {{ $attendanceLog-&gt;am_in ? $attendanceLog-&gt;am_in-&gt;format('h:i A') : 'N/A' }}
                            @break
                        @case('am_out')
                            {{ $attendanceLog-&gt;am_out ? $attendanceLog-&gt;am_out-&gt;format('h:i A') : 'N/A' }}
                            @break
                        @case('pm_in')
                            {{ $attendanceLog-&gt;pm_in ? $attendanceLog-&gt;pm_in-&gt;format('h:i A') : 'N/A' }}
                            @break
                        @case('pm_out')
                            {{ $attendanceLog-&gt;pm_out ? $attendanceLog-&gt;pm_out-&gt;format('h:i A') : 'N/A' }}
                            @break
                    @endswitch
                &lt;/p&gt;

                @if($attendanceLog-&gt;late_minutes &gt; 0)
                    &lt;p&gt;&lt;strong&gt;Late Minutes:&lt;/strong&gt; {{ $attendanceLog-&gt;late_minutes }} minutes&lt;/p&gt;
                @endif

                @if($attendanceLog-&gt;overtime_hours &gt; 0)
                    &lt;p&gt;&lt;strong&gt;Overtime Hours:&lt;/strong&gt; {{ $attendanceLog-&gt;overtime_hours }} hours&lt;/p&gt;
                @endif

                &lt;p&gt;&lt;strong&gt;Status:&lt;/strong&gt; {{ $attendanceLog-&gt;status }}&lt;/p&gt;
            &lt;/div&gt;

            &lt;p&gt;You can view your complete attendance record by logging into your account and checking your DTR (Daily Time Record).&lt;/p&gt;

            &lt;p&gt;Best regards,&lt;br&gt;
            MTCGS Attendance System&lt;/p&gt;
        &lt;/div&gt;

        &lt;div class="footer"&gt;
            &lt;p&gt;This is an automated message. Please do not reply to this email.&lt;/p&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/body&gt;
&lt;/html&gt;