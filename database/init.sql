CREATE DATABASE IF NOT EXISTS task_portal;
USE task_portal;

-- Reset tables in foreign-key dependency order
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS project_users;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;

-- 1. Users Table (Strictly 2 roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Administrator', 'Team Member') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Projects Table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    status ENUM('Active', 'Completed', 'On Hold') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Project Membership Junction Table
CREATE TABLE project_users (
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Tasks Table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    assigned_user_id INT NOT NULL,
    created_by INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 5. Comments Table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Section 10.4 Sample Accounts (Password: InternDemo!2026)
-- The hash changes with each generation, so the actual hash may differ from the one below. Use the provided password for login.
INSERT INTO users (id, name, email, password, role, status) VALUES
(1, 'Admin User', 'admin@example.test', '$2y$10$8caYbU/FMjokV40OFECIGOA.S0FGfLhpEeTxsEnXgGlIADbY9FfmG', 'Administrator', 'Active'),
(2, 'Member One', 'member1@example.test', '$2y$10$0Liu52HtGndiNNDLYy3qjOaB0..y5vH0nC1ypUGLBbjno0WJIS4ba', 'Team Member', 'Active'),
(3, 'Member Two', 'member2@example.test', '$2y$10$1uXGy97.7yHYCIH7BvShoOXqMcyfEaZThJ5hGbrIZs8cnCw8uFbH2', 'Team Member', 'Active');

-- Seed Demonstration Projects
INSERT INTO projects (id, name, description, status) VALUES
(1, 'Website Redesign', 'Complete migration of client portal to modern responsive UI.', 'Active'),
(2, 'Internal Helpdesk', 'Ticketing and support resolution workflow system.', 'Active');

-- Seed Project Memberships (Linked strictly to IDs 1, 2, 3)
INSERT INTO project_users (project_id, user_id) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 2),
(2, 3);

-- Seed Demonstration Tasks (Assigned strictly to IDs 2 and 3)
INSERT INTO tasks (id, project_id, assigned_user_id, created_by, title, description, priority, status, due_date) VALUES
(1, 1, 2, 1, 'Design login page wireframe', 'Create airy, modern Figma components for authentication screens.', 'High', 'Pending', '2026-09-20'),
(2, 1, 3, 1, 'Build Docker environment', 'Configure multi-container setup with PHP Apache and MySQL.', 'Medium', 'In Progress', '2026-09-22'),
(3, 1, 2, 1, 'Review UX responsiveness', 'Verify mobile menu behaviors and table scaffolding across devices.', 'Low', 'Completed', '2026-09-15'),
(4, 2, 2, 1, 'Configure database migrations', 'Write reproducible SQL script with foreign key constraints.', 'High', 'In Progress', '2026-09-18'),
(5, 2, 3, 1, 'Setup ticket submission forms', 'Implement validation for required input fields and sanitization.', 'Medium', 'Pending', '2026-09-25');
(6, 2, 3, 1, 'Audit user role permissions', 'Verify Administrator vs Team Member endpoint restrictions.', 'Low', 'Completed', '2026-09-14');

-- Seed Comments (Linked strictly to tasks 1 & 2 and users 1, 2, 3)
INSERT INTO comments (task_id, user_id, comment, created_at) VALUES
(2, 3, 'Initial Docker Compose file created. Testing MySQL connection via PDO.', '2026-09-14 09:30:00'),
(2, 1, 'Looks good. Ensure ports 8080 and 3306 are mapped correctly.', '2026-09-14 11:15:00'),
(2, 3, 'Verified! Apache is serving files out of the public folder without issues.', '2026-09-15 08:45:00'),
(1, 2, 'Drafting initial prototypes based on reference cards.', '2026-09-15 10:00:00');