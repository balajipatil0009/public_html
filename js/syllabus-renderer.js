// Universal syllabus renderer
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('syllabusContainer');
    
    if (!container || typeof syllabusData === 'undefined') {
        console.error('Syllabus container or data not found');
        return;
    }
    
    container.innerHTML = syllabusData.map((item, index) => {
        const isLeft = index % 2 === 0;
        const cardClass = isLeft ? 'syllabus-card-left' : 'syllabus-card-right';
        
        return `
            <div class="syllabus-card-container">
                <div class="syllabus-card-wrapper">
                    <div class="syllabus-card">
                        <div class="${cardClass}">
                            <div class="syllabus-card-content">
                                <h1 class="syllabus-card-title">${item.title}</h1>
                                <div class="syllabus-card-list">
                                    <ul>
                                        ${item.points.map(point => `<li>${point}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                            <div class="syllabus-timeline-line"></div>
                            <div class="syllabus-timeline-dot"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
});
