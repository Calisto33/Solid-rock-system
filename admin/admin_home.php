<?php

$page_title = "Dashboard";
$active_page = "dashboard"; 
include 'admin_header.php';

// PHP logic specific to the dashboard page
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'] ?? 0;
$totalStaff = $conn->query("SELECT COUNT(*) as count FROM staff")->fetch_assoc()['count'] ?? 0;
$totalParents = $conn->query("SELECT COUNT(*) as count FROM parents")->fetch_assoc()['count'] ?? 0;

$passingStudents = 0;
$failingStudents = 0;
// --- FIX: Changed 'year' to 'academic_year' in the query below ---
$sqlPerformance = "SELECT SUM(IF(final_mark >= 50, 1, 0)) as passing_count, SUM(IF(final_mark < 50, 1, 0)) as failing_count FROM results WHERE academic_year = '2025'";
$resultPerformance = $conn->query($sqlPerformance);
if ($resultPerformance) {
    $row = $resultPerformance->fetch_assoc();
    $passingStudents = $row['passing_count'] ?? 0;
    $failingStudents = $row['failing_count'] ?? 0;
}
?>

<main style="display: flex; flex-wrap: wrap; gap: 1.5rem;">
    <div style="flex: 3; min-width: 300px; display: flex; flex-direction: column; gap: 1.5rem;">
        <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem;">
            <div style="background: #fff; padding: 1rem; border-radius: 10px; box-shadow: var(--shadow);">
                <h3>Total Students</h3>
                <div style="font-size: 1.8rem; font-weight: 700;"><?= htmlspecialchars($totalStudents) ?></div>
            </div>
            <div style="background: #fff; padding: 1rem; border-radius: 10px; box-shadow: var(--shadow);">
                <h3>Staff Members</h3>
                <div style="font-size: 1.8rem; font-weight: 700;"><?= htmlspecialchars($totalStaff) ?></div>
            </div>
            <div style="background: #fff; padding: 1rem; border-radius: 10px; box-shadow: var(--shadow);">
                <h3>Registered Parents</h3>
                <div style="font-size: 1.8rem; font-weight: 700;"><?= htmlspecialchars($totalParents) ?></div>
            </div>
        </section>

        <section style="background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: var(--shadow);">
            <h3>School Analytics</h3>
            <div style="position: relative; height: 320px;"><canvas id="mainChart"></canvas></div>
        </section>
    </div>

    <aside style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 1.5rem;">
        <section style="background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: var(--shadow);">
              <h3>Quick Access</h3>
              <ul style="list-style: none; display:flex; flex-direction:column; gap:0.75rem; padding-top: 0.5rem;">
                  <li><a href="manage_students.php" style="display:flex; align-items:center; text-decoration:none; color:var(--text-dark); padding:0.75rem; border-radius:8px; transition: background-color 0.2s ease;"><i class="fas fa-user-graduate" style="width:35px;"></i> Manage Students <span style="margin-left:auto;">&rarr;</span></a></li>
                  <li><a href="post_news.php" style="display:flex; align-items:center; text-decoration:none; color:var(--text-dark); padding:0.75rem; border-radius:8px; transition: background-color 0.2s ease;"><i class="fas fa-newspaper" style="width:35px;"></i> Post News <span style="margin-left:auto;">&rarr;</span></a></li>
              </ul>
        </section>
        
        <section style="background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: var(--shadow); text-align:center;">
              <h3>Student Performance</h3>
              <div style="position:relative; height:150px; margin-bottom:1rem;"><canvas id="performanceChart"></canvas></div>
              <div style="display:flex; justify-content:center; gap:1.5rem;">
                  <div><div style="font-size:1.25rem; font-weight:bold; color:var(--green);"><?= htmlspecialchars($passingStudents) ?></div><div style="font-size:0.8rem;">Passing</div></div>
                  <div><div style="font-size:1.25rem; font-weight:bold; color:var(--red);"><?= htmlspecialchars($failingStudents) ?></div><div style="font-size:0.8rem;">Failing</div></div>
              </div>
        </section>
    </aside>
</main>

<script>
// Scripts for this page only
document.addEventListener('DOMContentLoaded', () => {
    const mainCtx = document.getElementById('mainChart')?.getContext('2d');
    if(mainCtx) new Chart(mainCtx, { type: 'line', data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'], datasets: [{ label: 'New Students', data: [12, 19, 15, 25, 22], borderColor: '#2970FF', tension: 0.4 }, { label: 'New Staff', data: [2, 3, 3, 5, 4], borderColor: '#8B909A', tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false } });
    
    const performanceCtx = document.getElementById('performanceChart')?.getContext('2d');
    if(performanceCtx) new Chart(performanceCtx, { type: 'doughnut', data: { labels: ['Passing', 'Failing'], datasets: [{ data: [<?= $passingStudents ?>, <?= $failingStudents ?>], backgroundColor: ['#00B69B', '#F84960'], borderWidth: 0, }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '80%' } });
});
</script>

<?php
include 'admin_footer.php';
?>