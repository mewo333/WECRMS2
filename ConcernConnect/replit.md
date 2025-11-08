# Employee Concern and Request Management System

## Overview

A comprehensive web-based HR management system for handling employee concerns and requests. Built with PHP 8.2, PostgreSQL, and vanilla JavaScript, this system provides ticketing, status tracking, chatbot assistance, and employee profile management. The application features a modern purple/blue gradient design matching the provided mockups.

**Demo Login:** Employee ID: `HR001` | Password: `admin123`

## Recent Changes (October 2025)

- Complete rebuild of the Employee Concern and Request Management System
- Implemented full ticket CRUD operations with status tracking
- Added AI chatbot with pre-configured HR responses
- Created employee dashboard with real-time statistics
- Built profile and settings management pages
- Integrated PostgreSQL database with proper schema design
- Added unique constraint for status tracking data integrity

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Technology Stack

**Backend:**
- PHP 8.2 with PDO for database operations
- PostgreSQL (Neon-backed Replit database)
- Session-based authentication
- RESTful API endpoints for chatbot

**Frontend:**
- Vanilla JavaScript (no frameworks)
- Custom CSS with gradient design (#667eea to #764ba2)
- Responsive grid layouts
- Real-time chat interface

### Database Schema

**Tables:**
1. `users` - Employee accounts with authentication
2. `tickets` - Employee concerns and requests
3. `chat_messages` - Chatbot conversation history
4. `status_updates` - Ticket status change tracking
5. `chatbot_responses` - Pre-configured chatbot responses

**Key Relationships:**
- Users have many tickets
- Users have many chat messages
- Users have many status updates
- Tickets link to status updates via user_id

### Application Structure

```
/
├── config/
│   └── database.php          # Database connection class
├── includes/
│   ├── auth.php             # Authentication functions
│   ├── functions.php        # Utility functions
│   └── sidebar.php          # Sidebar navigation component
├── api/
│   └── chatbot.php          # Chatbot API endpoint
├── assets/
│   ├── css/
│   │   └── style.css        # Main stylesheet
│   ├── js/
│   │   ├── chatbot.js       # Chatbot functionality
│   │   ├── dashboard.js     # Dashboard widgets
│   │   └── tickets.js       # Ticket filtering
│   └── images/
│       └── avatar.png       # User avatar placeholder
├── login.php                # Login page
├── register.php             # Registration page
├── dashboard.php            # Main dashboard
├── tickets.php              # Tickets list view
├── ticket_detail.php        # Ticket details and status update
├── create_ticket.php        # New ticket creation
├── profile.php              # User profile page
├── settings.php             # Account settings
├── status_update.php        # Status tracking dashboard
├── logout.php               # Logout handler
└── index.php                # Redirects to login
```

### Core Features

**1. Authentication System**
- Employee ID and password login
- Registration with email verification
- Session-based authentication
- Password hashing with bcrypt

**2. Ticket Management**
- Create tickets with categories (Leave, Payroll, Benefits, Equipment, IT Support)
- Priority levels (Low, Medium, High)
- Status tracking (Pending, In Progress, Resolved, Closed)
- Search and filter functionality
- Ticket detail view with status updates

**3. Status Tracking**
- Real-time status updates when tickets change
- Persistent tracking in status_updates table
- Dashboard metrics showing:
  - Active days count
  - Completed tasks
  - Tasks in progress
- Proper upsert with increment on status changes

**4. Chatbot Assistant**
- Pre-configured responses for common HR queries
- Keywords: leave, payroll, benefits, equipment, help, ticket
- Chat history persistence
- Real-time message sending
- Quick action buttons

**5. Dashboard Features**
- Live clock with shift duration
- Dynamic calendar with current day highlighting
- Status report widgets with color coding
- Recent tickets timeline
- Chat history preview
- Upcoming events display

**6. Profile & Settings**
- Employee profile with contact information
- Email and phone management
- Two-step verification toggle
- Password management
- Account preferences (language, timezone, nationality)

### Security Features

- Password hashing using PHP's password_hash()
- Prepared statements for SQL injection prevention
- Session-based access control
- Input sanitization with htmlspecialchars()
- CSRF protection via session validation

### API Endpoints

**POST /api/chatbot.php**
- Saves user message
- Retrieves bot response based on keywords
- Returns JSON response

**GET /api/chatbot.php**
- Retrieves chat history for current user
- Returns last 20 messages

### UI/UX Design

**Color Scheme:**
- Primary gradient: #667eea to #764ba2
- Success: #10B981
- Warning: #F59E0B
- Info: #3B82F6
- Danger: #EF4444

**Typography:**
- System font stack for native look
- Responsive font sizing
- Clear hierarchy with headings

**Layout:**
- Sidebar navigation (250px fixed)
- Main content area (responsive)
- Card-based components
- Grid layouts for statistics
- Floating chatbot widget

### Development Notes

**Database Migrations:**
- Use execute_sql_tool for schema changes
- Unique constraints on status_updates (user_id, status_type, status_date)
- Proper foreign key relationships

**Status Tracking Logic:**
- Dashboard metrics pull from tickets table for accuracy
- status_updates table tracks historical changes
- Upsert pattern prevents duplicate daily records
- Increment count on status changes

**Chatbot Response Matching:**
1. Exact keyword match from database
2. Partial keyword match (contains)
3. Fallback to generic help message

### Future Enhancements

- Email notifications for ticket updates
- File attachment support for tickets
- HR admin panel for ticket management
- Advanced analytics and reporting
- Department-based ticket routing
- Multi-language support
- Export ticket data to CSV/PDF
