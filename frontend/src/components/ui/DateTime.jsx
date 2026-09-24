export default function DateTime({ value, multiline = false }) {
    if (!value) {
        return '-';
    }

    const normalizedValue = String(value).replace('T', ' ');
    const [date, rawTime] = normalizedValue.split(' ');
    const time = rawTime
        ?.replace(/\.\d+(Z)?$/, '')
        .replace(/Z$/, '');

    if (!time) {
        return <span className="date-time">{date}</span>;
    }

    return multiline ? (
        <span className="date-time date-time-multiline">
            <span>{date}</span>
            <span>{time}</span>
        </span>
    ) : (
        <span className="date-time">{date} {time}</span>
    );
}
