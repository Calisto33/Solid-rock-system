<?php
// Set the page title for this specific page
$pageTitle = "AI Assistant";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';
?>

<style>
    /* Main Content */
    .page-content { /* Override default padding for a cleaner look */
        padding: 0;
    }
    .card {
        background-color: var(--card-bg);
        border-radius: 0; /* Make card flush with edges */
        box-shadow: none;
        padding: 3rem 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }
    .card-title {
        color: var(--primary-color);
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .card-description {
        color: var(--text-light);
        font-size: 1.125rem;
        max-width: 600px;
        margin: 0 auto 2rem;
    }
    /* Button Styles */
    .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: var(--white);
        padding: 1rem 2rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
        min-width: 240px;
    }
    .btn-primary:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    .btn-primary svg {
        margin-right: 0.75rem;
        height: 1.25rem;
        width: 1.25rem;
    }

    /* Tools Section */
    .tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        padding: 0 2rem 2rem 2rem; /* Add padding for the grid */
    }
    .tool-card {
        background-color: var(--card-bg);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        padding: 1.5rem;
        text-align: center;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .tool-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    .tool-icon {
        background-color: rgba(37, 99, 235, 0.1);
        height: 3rem;
        width: 3rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
    .tool-icon svg {
        height: 1.5rem;
        width: 1.5rem;
        fill: var(--primary-color);
    }
    .tool-title {
        color: var(--text-color);
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .tool-description {
        color: var(--text-light);
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
        flex-grow: 1;
    }
    .btn-tool {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(37, 99, 235, 0.1);
        color: var(--primary-color);
        padding: 0.5rem 1rem;
        border-radius: var(--radius-sm);
        font-weight: 500;
        font-size: 0.875rem;
        text-decoration: none;
        transition: var(--transition);
    }
    .btn-tool:hover {
        background-color: rgba(37, 99, 235, 0.2);
    }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">AI Teacher Assistant</h2>
        <p class="card-description">
            Access our intelligent teaching assistant to streamline your classroom management,
            create engaging lesson plans, and generate educational resources in seconds.
        </p>
    </div>
    <a href="https://www.chikoro-ai.com" target="_blank" class="btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
        </svg>
        Launch AI Teacher Assistant
    </a>
</div>

<div class="tools-grid">
    <div class="tool-card">
        <div class="tool-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z" /><path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" /></svg>
        </div>
        <h3 class="tool-title">Lesson Planner</h3>
        <p class="tool-description">
            Create comprehensive lesson plans with objectives, activities, and assessments aligned with curriculum standards.
        </p>
        <a href="#" class="btn-tool">Access Tool</a>
    </div>

    <div class="tool-card">
        <div class="tool-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 005 10a4 4 0 011.13-2.777A4.977 4.977 0 018 6c1.821 0 3.424.997 4.272 2.477A4.998 4.998 0 0110 11a4.998 4.998 0 01-2.272-.523zm4.272 1.116a5.01 5.01 0 01-2.272.523 4.998 4.998 0 01-2.272-.523A4.998 4.998 0 0114 11c0-1.821-.997-3.424-2.477-4.272A4.998 4.998 0 0110 11a5.002 5.002 0 01-2.446-.654c-.017.013-.034.027-.05.04a5.969 5.969 0 01-1.353 2.669 5.317 5.317 0 01-.168.146A8.004 8.004 0 0014 10c0-1.091-.22-2.13-.618-3.078a5.007 5.007 0 01-.108 4.193z" clip-rule="evenodd" /></svg>
        </div>
        <h3 class="tool-title">Student Management</h3>
        <p class="tool-description">
            Track attendance, manage grades, and monitor student progress with our intuitive management system.
        </p>
        <a href="#" class="btn-tool">Access Tool</a>
    </div>

    <div class="tool-card">
        <div class="tool-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" /></svg>
        </div>
        <h3 class="tool-title">Resource Library</h3>
        <p class="tool-description">
            Access a vast collection of teaching materials, worksheets, and educational resources for all subjects.
        </p>
        <a href="#" class="btn-tool">Access Tool</a>
    </div>
</div>

<?php
// Include the new footer to close the page layout
include 'footer.php';
?>