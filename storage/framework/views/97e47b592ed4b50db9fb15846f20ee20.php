<?php if($birthdayProfile?->date_of_birth && $birthdayProfile->date_of_birth->format('m-d') === now()->format('m-d')): ?>
    <div class="birthday-banner mb-4" role="status">
        <div class="birthday-confetti" aria-hidden="true">&#10024; &#127881; &#127873; &#10024;</div>
        <div class="birthday-content">
            <div class="birthday-icon" aria-hidden="true">&#127874;</div>
            <div>
                <div class="birthday-kicker">Today is your special day</div>
                <h2 class="birthday-title">Happy Birthday, <?php echo e($birthdayProfile->first_name); ?>!</h2>
                <p class="birthday-message mb-0">Wishing you a wonderful birthday and a year filled with happiness and success.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php $__env->startPush('styles'); ?>
<style>
    .birthday-banner {
        position: relative;
        overflow: hidden;
        border: 1px solid #f6c453;
        border-radius: 16px;
        background: linear-gradient(120deg, #fff7d6 0%, #ffe7b3 48%, #ffd2df 100%);
        box-shadow: 0 10px 24px rgba(180, 83, 9, 0.12);
        color: #713f12;
    }
    .birthday-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.2rem 1.5rem;
    }
    .birthday-icon {
        display: grid;
        place-items: center;
        width: 58px;
        height: 58px;
        flex: 0 0 58px;
        border-radius: 50%;
        background: #fff;
        font-size: 2rem;
        box-shadow: 0 5px 12px rgba(146, 64, 14, 0.14);
    }
    .birthday-kicker {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #b45309;
    }
    .birthday-title {
        margin: 0.15rem 0 0.25rem;
        color: #9a3412;
        font-size: clamp(1.35rem, 3vw, 2rem);
        font-weight: 800;
    }
    .birthday-message { color: #854d0e; }
    .birthday-confetti {
        position: absolute;
        top: 0.45rem;
        right: 1.25rem;
        color: #db2777;
        font-size: 1.1rem;
        letter-spacing: 0.35rem;
        opacity: 0.8;
    }
    @media (max-width: 576px) {
        .birthday-content { padding: 1rem; }
        .birthday-icon { width: 46px; height: 46px; flex-basis: 46px; font-size: 1.5rem; }
        .birthday-message { font-size: 0.88rem; }
    }
</style>
<?php $__env->stopPush(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/dashboard/birthday-banner.blade.php ENDPATH**/ ?>