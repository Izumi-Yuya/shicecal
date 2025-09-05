# Task Completion Guidelines

## When a Task is Completed

### 1. Code Quality Checks
```bash
# Format code
./vendor/bin/pint

# Run tests
php artisan test
npm run test
```

### 2. Verification Steps
- Ensure all tests pass
- Verify functionality works as expected
- Check that requirements are met
- Validate error handling

### 3. Documentation Updates
- Update relevant documentation if needed
- Add comments for complex logic
- Update API documentation if applicable

### 4. Git Workflow
```bash
# Stage changes
git add .

# Commit with descriptive message
git commit -m "feat: implement [feature description]"

# Push to feature branch
git push origin feature/branch-name
```

### 5. Testing Checklist
- [ ] Unit tests pass
- [ ] Feature tests pass
- [ ] Manual testing completed
- [ ] Edge cases considered
- [ ] Error scenarios tested

### 6. Code Review Preparation
- Ensure code follows Laravel conventions
- Check for security vulnerabilities
- Verify performance considerations
- Confirm accessibility requirements